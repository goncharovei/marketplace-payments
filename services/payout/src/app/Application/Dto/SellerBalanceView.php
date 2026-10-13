<?php

declare(strict_types=1);

namespace App\Application\Dto;

/**
 * Read-only representation of a SellerBalance for the API layer.
 */
final readonly class SellerBalanceView
{
    public function __construct(
        public string $sellerId,
        public int $available,
        public int $reserved,
        public int $debt,
        public string $currency,
        public string $updatedAt,
    ) {}
}
