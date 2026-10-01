<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\BuyerId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\SellerId;
use DateTimeImmutable;

final readonly class OrderCreated implements DomainEvent
{
    public function __construct(
        public OrderId $orderId,
        public BuyerId $buyerId,
        public SellerId $sellerId,
        public Money $totalAmount,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function now(
        OrderId $orderId,
        BuyerId $buyerId,
        SellerId $sellerId,
        Money $totalAmount,
    ): self {
        return new self($orderId, $buyerId, $sellerId, $totalAmount, new DateTimeImmutable);
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
