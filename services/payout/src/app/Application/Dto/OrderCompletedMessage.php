<?php

declare(strict_types=1);

namespace App\Application\Dto;

/**
 * Incoming message from the Order Service.
 * Published when an order is marked as completed.
 */
final readonly class OrderCompletedMessage
{
    public function __construct(
        public string $orderId,
        public string $sellerId,
        public int $amount,
        public string $currency,
    ) {}
}
