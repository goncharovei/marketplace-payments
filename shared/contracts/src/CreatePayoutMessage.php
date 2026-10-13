<?php

declare(strict_types=1);

namespace Shared\Contracts;

/**
 * Cross-service contract: Order Service → Payout Service.
 *
 * Command sent by the Saga once the order is paid.
 */
final readonly class CreatePayoutMessage
{
    public function __construct(
        public string $orderId,
        public string $sellerId,
        public int $amount,
        public string $currency,
    ) {}
}