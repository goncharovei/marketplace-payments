<?php

declare(strict_types=1);

namespace App\Application\Dto;

use App\Domain\Event\DomainEvent;
use DateTimeImmutable;

/**
 * Internal event raised by OrderFulfillmentSaga to request a payout.
 * Consumed by PayoutRequestedNotifier which talks to the Payout Service.
 */
final readonly class PayoutRequested implements DomainEvent
{
    public function __construct(
        public string $orderId,
        public string $sellerId,
        public int $amount,
        public string $currency,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function now(
        string $orderId,
        string $sellerId,
        int $amount,
        string $currency,
    ): self {
        return new self($orderId, $sellerId, $amount, $currency, new DateTimeImmutable);
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
