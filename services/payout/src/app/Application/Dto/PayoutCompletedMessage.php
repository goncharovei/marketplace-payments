<?php

declare(strict_types=1);

namespace App\Application\Dto;

/**
 * Outgoing message to the Order Service.
 * Published when a payout is successfully sent to the seller.
 */
final readonly class PayoutCompletedMessage
{
    public function __construct(
        public string $payoutId,
        public string $orderId,
        public string $sellerId,
        public int $amount,
        public string $currency,
    ) {}
}
