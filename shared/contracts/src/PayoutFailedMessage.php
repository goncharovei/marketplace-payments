<?php

declare(strict_types=1);

namespace Shared\Contracts;

/**
 * Cross-service contract: Payout Service → Order Service.
 *
 * Published when a payout has been escalated (retries exhausted).
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