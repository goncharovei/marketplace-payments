<?php

declare(strict_types=1);

namespace App\Application\Command;

/**
 * Command to start payment processing for an existing order.
 */
final readonly class StartPaymentCommand
{
    public function __construct(
        public string $orderId,
    ) {}
}
