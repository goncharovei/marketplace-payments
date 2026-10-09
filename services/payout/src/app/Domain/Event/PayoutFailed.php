<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PayoutId;
use App\Domain\ValueObject\SellerId;
use DateTimeImmutable;

final readonly class PayoutFailed implements DomainEvent
{
    public function __construct(
        public PayoutId $payoutId,
        public OrderId $orderId,
        public SellerId $sellerId,
        public string $reason,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function now(
        PayoutId $payoutId,
        OrderId $orderId,
        SellerId $sellerId,
        string $reason,
    ): self {
        return new self(
            $payoutId,
            $orderId,
            $sellerId,
            $reason,
            new DateTimeImmutable,
        );
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
