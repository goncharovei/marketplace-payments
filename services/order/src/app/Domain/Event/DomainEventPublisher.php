<?php

declare(strict_types=1);

namespace App\Domain\Event;

/**
 * Publishes domain events to the outside world.
 *
 * Implementations live in Infrastructure. The Domain layer depends
 * only on this contract.
 */
interface DomainEventPublisher
{
    /**
     * Publishes one or more domain events.
     */
    public function publish(DomainEvent ...$events): void;
}
