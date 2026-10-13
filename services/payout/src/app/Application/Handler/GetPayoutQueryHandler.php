<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Dto\PayoutView;
use App\Application\Query\GetPayoutQuery;
use App\Domain\Exception\PayoutNotFoundException;
use App\Domain\Model\Payout;
use App\Domain\Repository\PayoutRepository;
use App\Domain\ValueObject\PayoutId;
use Ecotone\Modelling\Attribute\QueryHandler;

final readonly class GetPayoutQueryHandler
{
    public function __construct(
        private PayoutRepository $payouts,
    ) {}

    #[QueryHandler]
    public function handle(GetPayoutQuery $query): PayoutView
    {
        $payoutId = PayoutId::fromString($query->payoutId);
        $payout = $this->payouts->findById($payoutId);

        if ($payout === null) {
            throw PayoutNotFoundException::withPayoutId($payoutId);
        }

        return $this->toView($payout);
    }

    private function toView(Payout $payout): PayoutView
    {
        return new PayoutView(
            id: $payout->id()->toString(),
            orderId: $payout->orderId()->toString(),
            sellerId: $payout->sellerId()->toString(),
            status: $payout->status()->value,
            amount: $payout->amount()->amount(),
            platformFee: $payout->platformFee()->amount(),
            currency: $payout->amount()->currency(),
            method: $payout->method()->value,
            externalId: $payout->externalId()?->toString(),
            retryCount: $payout->retryCount(),
            createdAt: $payout->createdAt()->format(DATE_ATOM),
            updatedAt: $payout->updatedAt()->format(DATE_ATOM),
        );
    }
}
