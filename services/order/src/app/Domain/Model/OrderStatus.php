<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Lifecycle states of the Order aggregate.
 */
enum OrderStatus: string
{
    case Created = 'created';
    case PaymentProcessing = 'payment_processing';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function isFinal(): bool
    {
        return match ($this) {
            self::Cancelled, self::Refunded => true,
            default => false,
        };
    }

    public function canBePaid(): bool
    {
        return $this === self::Created;
    }

    public function canBeCancelled(): bool
    {
        return match ($this) {
            self::Created, self::PaymentProcessing, self::Paid => true,
            default => false,
        };
    }
}
