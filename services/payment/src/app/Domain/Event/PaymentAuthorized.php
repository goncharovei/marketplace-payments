<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\ExternalTransactionId;
use App\Domain\ValueObject\PaymentId;
use DateTimeImmutable;

final readonly class PaymentAuthorized implements DomainEvent
{
    public function __construct(
        public PaymentId $paymentId,
        public ExternalTransactionId $externalId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function now(PaymentId $paymentId, ExternalTransactionId $externalId): self
    {
        return new self($paymentId, $externalId, new DateTimeImmutable);
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
