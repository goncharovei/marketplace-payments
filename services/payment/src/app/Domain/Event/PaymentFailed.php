<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\PaymentId;
use DateTimeImmutable;

final readonly class PaymentFailed implements DomainEvent
{
    public function __construct(
        public PaymentId $paymentId,
        public string $reason,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function now(PaymentId $paymentId, string $reason): self
    {
        return new self($paymentId, $reason, new DateTimeImmutable);
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
