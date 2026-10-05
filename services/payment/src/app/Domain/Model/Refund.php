<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\RefundId;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Refund entity - internal to the Payment aggregate.
 *
 * Unlike Payment, Refund is not an aggregate root. It is always accessed
 * through its parent Payment and does not have a global identity.
 */
final class Refund
{
    private function __construct(
        private RefundId $id,
        private Money $amount,
        private string $reason,
        private DateTimeImmutable $createdAt,
    ) {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Refund reason cannot be empty.');
        }
    }

    public static function create(RefundId $id, Money $amount, string $reason): self
    {
        return new self($id, $amount, $reason, new DateTimeImmutable);
    }

    public static function restore(
        RefundId $id,
        Money $amount,
        string $reason,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $amount, $reason, $createdAt);
    }

    public function id(): RefundId
    {
        return $this->id;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
