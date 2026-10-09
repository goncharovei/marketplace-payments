<?php

declare(strict_types=1);

namespace App\Application\Dto;

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
