<?php

declare(strict_types=1);

namespace App\Application\Dto;

final readonly class CapturePaymentMessage
{
    public function __construct(
        public string $orderId,
        public int $amount,
        public string $currency,
    ) {}
}
