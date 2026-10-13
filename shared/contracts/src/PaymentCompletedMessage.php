<?php

declare(strict_types=1);

namespace Shared\Contracts;

/**
 * Cross-service contract: Payment Service → Order Service.
 *
 * Published when a payment is captured.
 */
final readonly class PaymentCompletedMessage
{
    public function __construct(
        public string $paymentId,
        public string $orderId,
        public int $amount,
        public string $currency,
    ) {}
}