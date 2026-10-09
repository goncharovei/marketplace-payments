<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\PayoutId;
use DateTimeImmutable;

final readonly class PayoutFailed implements DomainEvent
{
    public function __construct(
        public PayoutId $payoutId,
        public string $reason,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function now(PayoutId $payoutId, string $reason): self
    {
        return new self($payoutId, $reason, new DateTimeImmutable);
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
