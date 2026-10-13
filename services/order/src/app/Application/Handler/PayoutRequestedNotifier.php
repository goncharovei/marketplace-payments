<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Dto\PayoutRequested;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\DistributedBus;
use Shared\Contracts\CreatePayoutMessage;

final readonly class PayoutRequestedNotifier
{
    public function __construct(
        private DistributedBus $distributedBus,
    ) {}

    #[EventHandler]
    public function handle(PayoutRequested $event): void
    {
        $this->distributedBus->convertAndSendCommand(
            targetServiceName: 'payout_service',
            routingKey: 'payout.create',
            command: new CreatePayoutMessage(
                orderId: $event->orderId,
                sellerId: $event->sellerId,
                amount: $event->amount,
                currency: $event->currency,
            ),
        );
    }
}
