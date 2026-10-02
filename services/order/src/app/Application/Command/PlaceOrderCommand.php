<?php

declare(strict_types=1);

namespace App\Application\Command;

/**
 * Command to place a new Order.
 */
final readonly class PlaceOrderCommand
{
    /**
     * @param  array<array{productId: string, quantity: int, amount: int, currency: string}>  $items
     */
    public function __construct(
        public string $buyerId,
        public string $sellerId,
        public array $items,
    ) {}
}
