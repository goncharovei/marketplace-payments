<?php

declare(strict_types=1);

namespace App\Application\Dto;

/**
 * Message published to the Distributed Bus when an order is created.
 *
 * This is a contract between Order Service and other services
 * (Payment, Payout). Keep it stable: it is serialized to JSON.
 */
final readonly class OrderCreatedMessage
{
    public function __construct(
        public string $orderId,
        public string $buyerId,
        public string $sellerId,
        public int $amount,
        public string $currency,
    ) {}
}
