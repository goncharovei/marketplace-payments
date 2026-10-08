<?php

declare(strict_types=1);

namespace App\Domain\Event;

use DateTimeImmutable;

/**
 * Marker interface for all domain events in the Payout Service.
 */
interface DomainEvent
{
    public function occurredAt(): DateTimeImmutable;
}
