<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\OrderId;
use DateTimeImmutable;

final readonly class OrderCancelled implements DomainEvent
{
    public function __construct(
        public OrderId $orderId,
        public string $reason,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function now(OrderId $orderId, string $reason): self
    {
        return new self($orderId, $reason, new DateTimeImmutable);
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
