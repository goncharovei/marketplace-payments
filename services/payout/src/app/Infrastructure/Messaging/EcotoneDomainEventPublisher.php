<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use App\Domain\Event\DomainEvent;
use App\Domain\Event\DomainEventPublisher;
use Ecotone\Modelling\EventBus;

final readonly class EcotoneDomainEventPublisher implements DomainEventPublisher
{
    public function __construct(
        private EventBus $eventBus,
    ) {}

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->eventBus->publish($event);
        }
    }
}
