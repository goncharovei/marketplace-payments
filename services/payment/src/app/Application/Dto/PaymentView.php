<?php

declare(strict_types=1);

namespace App\Application\Dto;

/**
 * Read-only representation of a Payment for the API layer.
 * Decoupled from the Domain aggregate.
 */
final readonly class PaymentView
{
    /**
     * @param  array<array{id: string, amount: int, currency: string, reason: string, createdAt: string}>  $refunds
     */
    public function __construct(
        public string $id,
        public string $orderId,
        public string $buyerId,
        public string $status,
        public int $amount,
        public string $currency,
        public int $refundedAmount,
        public string $method,
        public ?string $externalId,
        public array $refunds,
        public string $createdAt,
        public string $updatedAt,
    ) {}
}
