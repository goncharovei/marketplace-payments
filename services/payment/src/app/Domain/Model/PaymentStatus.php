<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Lifecycle states of the Payment aggregate.
 */
enum PaymentStatus: string
{
    case Initiated = 'initiated';
    case Authorized = 'authorized';
    case Captured = 'captured';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

    public function isFinal(): bool
    {
        return match ($this) {
            self::Failed, self::Refunded => true,
            default => false,
        };
    }

    public function canBeAuthorized(): bool
    {
        return $this === self::Initiated;
    }

    public function canBeCaptured(): bool
    {
        return $this === self::Authorized;
    }

    public function canBeRefunded(): bool
    {
        return match ($this) {
            self::Captured, self::PartiallyRefunded => true,
            default => false,
        };
    }
}
