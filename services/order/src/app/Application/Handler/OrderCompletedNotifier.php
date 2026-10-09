<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Dto\OrderCompletedMessage;
use App\Domain\Event\OrderCompleted;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\DistributedBus;

final readonly class OrderCompletedNotifier
{
    public function __construct(
        private DistributedBus $distributedBus,
    ) {}

    #[EventHandler]
    public function handle(OrderCompleted $event): void
    {
        $this->distributedBus->convertAndPublishEvent(
            routingKey: 'order.completed',
            event: new OrderCompletedMessage(
                orderId: $event->orderId->toString(),
                sellerId: $event->sellerId->toString(),
                amount: $event->amount->amount(),
                currency: $event->amount->currency(),
            ),
        );
    }
}
