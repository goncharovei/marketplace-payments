<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\ExternalPayoutId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PayoutId;
use App\Domain\ValueObject\SellerId;
use DateTimeImmutable;

final readonly class PayoutCompleted implements DomainEvent
{
    public function __construct(
        public PayoutId $payoutId,
        public OrderId $orderId,
        public SellerId $sellerId,
        public ExternalPayoutId $externalId,
        public Money $amount,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function now(
        PayoutId $payoutId,
        OrderId $orderId,
        SellerId $sellerId,
        ExternalPayoutId $externalId,
        Money $amount,
    ): self {
        return new self(
            $payoutId,
            $orderId,
            $sellerId,
            $externalId,
            $amount,
            new DateTimeImmutable,
        );
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
