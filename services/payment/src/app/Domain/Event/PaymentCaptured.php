<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\PaymentId;
use DateTimeImmutable;

final readonly class PaymentCaptured implements DomainEvent
{
    public function __construct(
        public PaymentId $paymentId,
        public Money $amount,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function now(PaymentId $paymentId, Money $amount): self
    {
        return new self($paymentId, $amount, new DateTimeImmutable);
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
