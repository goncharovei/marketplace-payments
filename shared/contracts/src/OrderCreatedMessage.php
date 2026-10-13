<?php

declare(strict_types=1);

namespace Shared\Contracts;

/**
 * Cross-service contract: Order Service → Payment Service.
 *
 * Published when an order is created. Payment Service consumes it to initiate a payment.
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