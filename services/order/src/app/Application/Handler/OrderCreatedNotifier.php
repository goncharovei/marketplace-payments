<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Dto\OrderCreatedMessage;
use App\Domain\Event\OrderCreated;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\DistributedBus;

final readonly class OrderCreatedNotifier
{
    public function __construct(
        private DistributedBus $distributedBus,
    ) {}

    #[EventHandler]
    public function handle(OrderCreated $event): void
    {
        $this->distributedBus->convertAndPublishEvent(
            routingKey: 'order.created',
            event: new OrderCreatedMessage(
                orderId: $event->orderId->toString(),
                buyerId: $event->buyerId->toString(),
                sellerId: $event->sellerId->toString(),
                amount: $event->totalAmount->amount(),
                currency: $event->totalAmount->currency(),
            )
        );
    }
}
