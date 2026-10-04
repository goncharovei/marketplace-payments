<?php

declare(strict_types=1);

namespace App\Application\Query;

/**
 * Query to fetch a single order by its identifier.
 */
final readonly class GetOrderQuery
{
    public function __construct(
        public string $orderId,
    ) {}
}
