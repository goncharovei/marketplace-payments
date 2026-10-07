<?php

declare(strict_types=1);

namespace App\Application\Command;

/**
 * Command to initiate a new payment for an order.
 */
final readonly class InitiatePaymentCommand
{
    public function __construct(
        public string $orderId,
        public string $buyerId,
        public int $amount,
        public string $currency,
        public string $method,
    ) {}
}
