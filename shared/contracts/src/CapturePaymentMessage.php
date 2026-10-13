<?php

declare(strict_types=1);

namespace Shared\Contracts;

/**
 * Cross-service contract: Order Service → Payment Service.
 *
 * Command sent when the order moves into PaymentProcessing state.
 */
final readonly class CapturePaymentMessage
{
    public function __construct(
        public string $orderId,
        public int $amount,
        public string $currency,
    ) {}
}