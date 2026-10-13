<?php

declare(strict_types=1);

namespace App\Application\Query;

/**
 * Query to fetch a Payment by the identifier of its parent Order.
 */
final readonly class GetPaymentByOrderQuery
{
    public function __construct(
        public string $orderId,
    ) {}
}
