<?php

declare(strict_types=1);

namespace App\Application\Command;

final readonly class CreatePayoutCommand
{
    public function __construct(
        public string $orderId,
        public string $sellerId,
        public int $amount,
        public string $currency,
    ) {}
}
