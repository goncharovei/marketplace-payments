<?php

declare(strict_types=1);

namespace App\Application\Dto;

use App\Domain\Event\DomainEvent;
use DateTimeImmutable;

/**
 * Internal event raised by PayoutFailedConsumer to signal the Saga
 * that the payout for the given order has failed.
 */
final readonly class PayoutFailedSignal implements DomainEvent
{
    public function __construct(
        public string $orderId,
        public string $reason,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function now(string $orderId, string $reason): self
    {
        return new self($orderId, $reason, new DateTimeImmutable);
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
