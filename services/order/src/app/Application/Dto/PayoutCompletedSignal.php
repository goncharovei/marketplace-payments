<?php

declare(strict_types=1);

namespace App\Application\Dto;

use App\Domain\Event\DomainEvent;
use DateTimeImmutable;

/**
 * Internal event raised by PayoutCompletedConsumer to signal the Saga
 * that the payout for the given order has completed.
 */
final readonly class PayoutCompletedSignal implements DomainEvent
{
    public function __construct(
        public string $orderId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function now(string $orderId): self
    {
        return new self($orderId, new DateTimeImmutable);
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
