<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Dto\PayoutCompletedMessage;
use App\Domain\Event\PayoutCompleted;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\DistributedBus;

final readonly class PayoutCompletedNotifier
{
    public function __construct(
        private DistributedBus $distributedBus,
    ) {}

    #[EventHandler]
    public function handle(PayoutCompleted $event): void
    {
        $this->distributedBus->convertAndPublishEvent(
            routingKey: 'payout.completed',
            event: new PayoutCompletedMessage(
                payoutId: $event->payoutId->toString(),
                orderId: $event->orderId->toString(),
                sellerId: $event->sellerId->toString(),
                amount: $event->amount->amount(),
                currency: $event->amount->currency(),
            ),
        );
    }
}
