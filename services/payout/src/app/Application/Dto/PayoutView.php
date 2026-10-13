<?php

declare(strict_types=1);

namespace App\Application\Dto;

/**
 * Read-only representation of a Payout for the API layer.
 * Decoupled from the Domain aggregate.
 */
final readonly class PayoutView
{
    public function __construct(
        public string $id,
        public string $orderId,
        public string $sellerId,
        public string $status,
        public int $amount,
        public int $platformFee,
        public string $currency,
        public string $method,
        public ?string $externalId,
        public int $retryCount,
        public string $createdAt,
        public string $updatedAt,
    ) {}
}
