<?php

declare(strict_types=1);

namespace App\Application\Dto;

final readonly class PayoutFailedMessage
{
    public function __construct(
        public string $payoutId,
        public string $orderId,
        public string $sellerId,
        public string $reason,
    ) {}
}
