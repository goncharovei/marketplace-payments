<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use DateTimeImmutable;

final readonly class OrderRefunded implements DomainEvent
{
    public function __construct(
        public OrderId $orderId,
        public Money $amount,
        public string $reason,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function now(OrderId $orderId, Money $amount, string $reason): self
    {
        return new self($orderId, $amount, $reason, new DateTimeImmutable);
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
