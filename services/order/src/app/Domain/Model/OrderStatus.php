<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Lifecycle states of the Order aggregate.
 */
enum OrderStatus: string
{
    case CREATED = 'created';
    case PAYMENT_PROCESSING = 'payment_processing';
    case PAID = 'paid';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function isFinal(): bool
    {
        return match ($this) {
            self::COMPLETED, self::CANCELLED, self::REFUNDED => true,
            default => false,
        };
    }

    public function canBePaid(): bool
    {
        return $this === self::CREATED;
    }

    public function canBeCompleted(): bool
    {
        return $this === self::PAID;
    }

    public function canBeCancelled(): bool
    {
        return match ($this) {
            self::CREATED, self::PAYMENT_PROCESSING, self::PAID => true,
            default => false,
        };
    }
}
