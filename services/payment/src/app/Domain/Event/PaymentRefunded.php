<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\RefundId;
use DateTimeImmutable;

final readonly class PaymentRefunded implements DomainEvent
{
    public function __construct(
        public PaymentId $paymentId,
        public RefundId $refundId,
        public Money $amount,
        public string $reason,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function now(
        PaymentId $paymentId,
        RefundId $refundId,
        Money $amount,
        string $reason,
    ): self {
        return new self($paymentId, $refundId, $amount, $reason, new DateTimeImmutable);
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
