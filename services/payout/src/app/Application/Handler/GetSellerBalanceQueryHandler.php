<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Dto\SellerBalanceView;
use App\Application\Query\GetSellerBalanceQuery;
use App\Domain\Repository\SellerBalanceRepository;
use App\Domain\ValueObject\SellerId;
use Ecotone\Modelling\Attribute\QueryHandler;

final readonly class GetSellerBalanceQueryHandler
{
    public function __construct(
        private SellerBalanceRepository $balances,
    ) {}

    #[QueryHandler]
    public function handle(GetSellerBalanceQuery $query): SellerBalanceView
    {
        $sellerId = SellerId::fromString($query->sellerId);
        $balance = $this->balances->findBySellerId($sellerId);

        // A seller with no payout history has a zeroed balance.
        // Return zeroes rather than 404 — a missing balance is not an error.
        if ($balance === null) {
            return new SellerBalanceView(
                sellerId: $sellerId->toString(),
                available: 0,
                reserved: 0,
                debt: 0,
                currency: $query->currency,
                updatedAt: (new \DateTimeImmutable)->format(DATE_ATOM),
            );
        }

        return new SellerBalanceView(
            sellerId: $balance->sellerId()->toString(),
            available: $balance->available()->amount(),
            reserved: $balance->reserved()->amount(),
            debt: $balance->debt()->amount(),
            currency: $balance->available()->currency(),
            updatedAt: $balance->updatedAt()->format(DATE_ATOM),
        );
    }
}
