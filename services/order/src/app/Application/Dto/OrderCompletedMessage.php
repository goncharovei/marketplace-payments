<?php

declare(strict_types=1);

namespace App\Application\Dto;

final readonly class OrderCompletedMessage
{
    public function __construct(
        public string $orderId,
        public string $sellerId,
        public int $amount,
        public string $currency,
    ) {}
}
