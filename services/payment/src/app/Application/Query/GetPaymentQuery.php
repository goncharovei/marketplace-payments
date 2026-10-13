<?php

declare(strict_types=1);

namespace App\Application\Query;

/**
 * Query to fetch a single Payment by its identifier.
 */
final readonly class GetPaymentQuery
{
    public function __construct(
        public string $paymentId,
    ) {}
}
