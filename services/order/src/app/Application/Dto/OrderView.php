<?php

declare(strict_types=1);

namespace App\Application\Dto;

/**
 * Read-only representation of an Order for the API layer.
 * Decoupled from the Domain aggregate.
 */
final readonly class OrderView
{
    /**
     * @param  array<array{productId: string, quantity: int, amount: int, currency: string}>  $items
     */
    public function __construct(
        public string $id,
        public string $buyerId,
        public string $sellerId,
        public string $status,
        public int $totalAmount,
        public string $currency,
        public array $items,
        public string $createdAt,
        public string $updatedAt,
    ) {}
}
