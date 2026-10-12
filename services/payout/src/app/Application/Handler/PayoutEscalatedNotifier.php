<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Dto\PayoutFailedMessage;
use App\Domain\Event\PayoutEscalated;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\DistributedBus;

/**
 * Publishes payout.failed to the Order Service only when the payout has
 * been permanently escalated (retries exhausted).
 *
 * Transient retries remain internal to the Payout Service.
 */
final readonly class PayoutEscalatedNotifier
{
    public function __construct(
        private DistributedBus $distributedBus,
    ) {}

    #[EventHandler]
    public function handle(PayoutEscalated $event): void
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
