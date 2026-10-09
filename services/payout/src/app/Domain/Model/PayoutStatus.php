<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Lifecycle states of the Payout aggregate.
 *
 * Pending → Processing → Completed
 *                    ↘ Failed → Processing (retry, up to MAX_RETRY_COUNT)
 *                             ↘ OnHold (manual review)
 */
enum PayoutStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case ON_HOLD = 'on_hold';

    public function isFinal(): bool
    {
        return $this === self::COMPLETED;
    }

    public function canBeStarted(): bool
    {
        return $this === self::PENDING;
    }

    public function canBeCompleted(): bool
    {
        return $this === self::PROCESSING;
    }

    public function canBeFailed(): bool
    {
        return $this === self::PROCESSING;
    }

    public function canBeRetried(): bool
    {
        return $this === self::FAILED;
    }

    public function canBeEscalated(): bool
    {
        return $this === self::FAILED;
    }
}
