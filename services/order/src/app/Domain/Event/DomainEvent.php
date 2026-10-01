<?php

declare(strict_types=1);

namespace App\Domain\Event;

use DateTimeImmutable;

/**
 * Marker interface for all domain events in the Order Service.
 * Every domain event carries the moment when it occurred.
 */
interface DomainEvent
{
    public function occurredAt(): DateTimeImmutable;
}
