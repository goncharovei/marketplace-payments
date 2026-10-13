<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Dto\PayoutView;
use App\Application\Query\GetPayoutByOrderQuery;
use App\Domain\Exception\PayoutNotFoundException;
use App\Domain\Model\Payout;
use App\Domain\Repository\PayoutRepository;
use App\Domain\ValueObject\OrderId;
use Ecotone\Modelling\Attribute\QueryHandler;

final readonly class GetPayoutByOrderQueryHandler
{
    public function __construct(
        private PayoutRepository $payouts,
    ) {}

    #[QueryHandler]
    public function handle(GetPayoutByOrderQuery $query): PayoutView
    {
        $orderId = OrderId::fromString($query->orderId);
        $payout = $this->payouts->findByOrderId($orderId);

        if ($payout === null) {
            throw PayoutNotFoundException::withOrderId($orderId);
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
