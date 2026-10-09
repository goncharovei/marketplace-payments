<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Dto\PayoutFailedMessage;
use App\Domain\Event\PayoutFailed;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\DistributedBus;

final readonly class PayoutFailedNotifier
{
    public function __construct(
        private DistributedBus $distributedBus,
    ) {}

    #[EventHandler]
    public function handle(PayoutFailed $event): void
    {
        $this->distributedBus->convertAndPublishEvent(
            routingKey: 'payout.failed',
            event: new PayoutFailedMessage(
                payoutId: $event->payoutId->toString(),
                orderId: $event->orderId->toString(),
                sellerId: $event->sellerId->toString(),
                reason: $event->reason,
            ),
        );
    }
}
