<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Model\PayoutMethod;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PayoutId;
use App\Domain\ValueObject\SellerId;
use DateTimeImmutable;

final readonly class PayoutCreated implements DomainEvent
{
    public function __construct(
        public PayoutId $payoutId,
        public SellerId $sellerId,
        public OrderId $orderId,
        public Money $amount,
        public Money $platformFee,
        public PayoutMethod $method,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function now(
        PayoutId $payoutId,
        SellerId $sellerId,
        OrderId $orderId,
        Money $amount,
        Money $platformFee,
        PayoutMethod $method,
    ): self {
        return new self(
            $payoutId,
            $sellerId,
            $orderId,
            $amount,
            $platformFee,
            $method,
            new DateTimeImmutable,
        );
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
