<?php

declare(strict_types=1);

namespace App\Application\Dto;

final readonly class CreatePayoutMessage
{
    public function __construct(
        public string $orderId,
        public string $sellerId,
        public int $amount,
        public string $currency,
    ) {}
}
