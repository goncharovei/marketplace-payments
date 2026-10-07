<?php

declare(strict_types=1);

namespace App\Application\Command;

final readonly class CapturePaymentCommand
{
    public function __construct(
        public string $paymentId,
    ) {}
}
