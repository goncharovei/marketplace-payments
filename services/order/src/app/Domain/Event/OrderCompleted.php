<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\SellerId;
use DateTimeImmutable;

final readonly class OrderCompleted implements DomainEvent
{
    public function __construct(
        public OrderId $orderId,
        public SellerId $sellerId,
        public Money $amount,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function now(
        OrderId $orderId,
        SellerId $sellerId,
        Money $amount,
    ): self {
        return new self($orderId, $sellerId, $amount, new DateTimeImmutable);
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
