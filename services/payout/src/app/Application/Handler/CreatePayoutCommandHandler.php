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

        // Reserve the payout amount from the seller's balance.
        $balance = $this->balances->findBySellerId($sellerId)
            ?? SellerBalance::start($sellerId, $command->currency);

        $balance->earn($orderAmount);
        $balance->reserve($payout->amount());
        $this->balances->save($balance);

        // Persist the payout in PENDING, then try to send it.
        $this->payouts->save($payout);

        $payout->start();

        try {
            $externalId = $this->gateway->send($payout->amount(), $payout->method());
            $payout->complete($externalId);
            $balance->deduct($payout->amount());
        } catch (PayoutGatewayException $e) {
            $this->logger->warning('Payout gateway rejected the transfer', [
                'payoutId' => $payout->id()->toString(),
                'reason' => $e->getMessage(),
            ]);

            $payout->fail($e->getMessage());
            $balance->release($payout->amount());
        }

        $this->payouts->save($payout);
        $this->balances->save($balance);

        return $payout->id();
    }
}
