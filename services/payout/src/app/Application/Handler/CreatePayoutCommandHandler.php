<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\CreatePayoutCommand;
use App\Domain\Exception\PayoutGatewayException;
use App\Domain\Model\Payout;
use App\Domain\Model\PayoutMethod;
use App\Domain\Model\SellerBalance;
use App\Domain\Repository\PayoutRepository;
use App\Domain\Repository\SellerBalanceRepository;
use App\Domain\Service\PayoutGateway;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PayoutId;
use App\Domain\ValueObject\PlatformFee;
use App\Domain\ValueObject\SellerId;
use Ecotone\Modelling\Attribute\CommandHandler;
use Psr\Log\LoggerInterface;

final readonly class CreatePayoutCommandHandler
{
    public function __construct(
        private PayoutRepository $payouts,
        private SellerBalanceRepository $balances,
        private PayoutGateway $gateway,
        private LoggerInterface $logger,
    ) {}

    #[CommandHandler]
    public function handle(CreatePayoutCommand $command): PayoutId
    {
        $sellerId = SellerId::fromString($command->sellerId);
        $orderId = OrderId::fromString($command->orderId);
        $orderAmount = Money::of($command->amount, $command->currency);

        // Idempotency: if a payout for this order already exists, return it.
        $existing = $this->payouts->findByOrderId($orderId);
        if ($existing !== null) {
            return $existing->id();
        }

        $payout = Payout::create(
            $this->payouts->nextIdentity(),
            $sellerId,
            $orderId,
            $orderAmount,
            PlatformFee::tenPercent(),
            PayoutMethod::BANK_TRANSFER,
        );

        $balance = $this->balances->findBySellerId($sellerId)
            ?? SellerBalance::start($sellerId, $command->currency);

        $balance->earn($payout->amount());
        $balance->reserve($payout->amount());

        $payout->start();

        $this->attemptSendOrEscalate($payout, $balance);

        $this->payouts->save($payout);
        $this->balances->save($balance);

        return $payout->id();
    }

    /**
     * Synchronous retry policy.
     *
     * Total attempts = MAX_RETRY_COUNT + 1 (initial + retries).
     * On transient gateway failure the amount stays reserved.
     * On permanent failure (retries exhausted) the payout is escalated
     * and the reserved amount is moved to the seller's debt — the
     * platform has already captured the buyer's payment, so the seller
     * owes the platform the unfulfilled payout.
     *
     * In production this loop should be replaced with #[Delayed] retries
     * and exponential backoff so the worker is not blocked.
     */
    private function attemptSendOrEscalate(Payout $payout, SellerBalance $balance): void
    {
        $attempts = Payout::MAX_RETRY_COUNT + 1;

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            try {
                $externalId = $this->gateway->send($payout->amount(), $payout->method());
                $payout->complete($externalId);
                $balance->deduct($payout->amount());

                return;
            } catch (PayoutGatewayException $e) {
                $reason = $e->getMessage();
                $payout->fail($reason);

                if ($payout->retryCount() >= Payout::MAX_RETRY_COUNT) {
                    $payout->escalateForManualReview($reason);
                    $balance->deduct($payout->amount());
                    $balance->increaseDebt($payout->amount());

                    $this->logger->error('Payout escalated after exhausting retries', [
                        'payoutId' => $payout->id()->toString(),
                        'retryCount' => $payout->retryCount(),
                        'reason' => $reason,
                    ]);

                    return;
                }

                $payout->retry();

                $this->logger->warning('Payout gateway failed, retrying', [
                    'payoutId' => $payout->id()->toString(),
                    'retryCount' => $payout->retryCount(),
                    'reason' => $reason,
                ]);
            }
        }
    }
}
