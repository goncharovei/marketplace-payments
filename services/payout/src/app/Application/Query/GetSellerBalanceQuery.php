<?php

declare(strict_types=1);

namespace App\Application\Query;

final readonly class GetSellerBalanceQuery
{
    public function __construct(
        public string $sellerId,
        public string $currency = 'RUB',
    ) {}
}
