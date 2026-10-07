<?php

declare(strict_types=1);

namespace App\Application\Command;

final readonly class AuthorizePaymentCommand
{
    public function __construct(
        public string $paymentId,
        public string $externalTransactionId,
    ) {}
}
