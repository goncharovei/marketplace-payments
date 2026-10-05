<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Lifecycle states of the Payment aggregate.
 */
enum PaymentStatus: string
{
    case INITIATED = 'initiated';
    case AUTHORIZED = 'authorized';
    case CAPTURED = 'captured';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';

    public function isFinal(): bool
    {
        return match ($this) {
            self::FAILED, self::REFUNDED => true,
            default => false,
        };
    }

    public function canBeAuthorized(): bool
    {
        return $this === self::INITIATED;
    }

    public function canBeCaptured(): bool
    {
        return $this === self::AUTHORIZED;
    }

    public function canBeRefunded(): bool
    {
        return match ($this) {
            self::CAPTURED, self::PARTIALLY_REFUNDED => true,
            default => false,
        };
    }
}
