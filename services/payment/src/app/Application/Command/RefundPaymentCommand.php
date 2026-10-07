<?php

declare(strict_types=1);

namespace App\Application\Command;

final readonly class RefundPaymentCommand
{
    public function __construct(
        public string $paymentId,
        public int $amount,
        public string $currency,
        public string $reason,
    ) {}
}
