<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Model\PaymentMethod;
use App\Domain\ValueObject\BuyerId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentId;
use DateTimeImmutable;

final readonly class PaymentInitiated implements DomainEvent
{
    public function __construct(
        public PaymentId $paymentId,
        public OrderId $orderId,
        public BuyerId $buyerId,
        public Money $amount,
        public PaymentMethod $method,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function now(
        PaymentId $paymentId,
        OrderId $orderId,
        BuyerId $buyerId,
        Money $amount,
        PaymentMethod $method,
    ): self {
        return new self($paymentId, $orderId, $buyerId, $amount, $method, new DateTimeImmutable);
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
