<?php

declare(strict_types=1);

namespace App\Application\Dto;

/**
 * Outgoing message to the Order Service.
 * Published when a payout fails permanently (after retries).
 */
final readonly class PayoutFailedMessage
{
    public function __construct(
        public string $payoutId,
        public string $orderId,
        public string $sellerId,
        public string $reason,
    ) {}
}
