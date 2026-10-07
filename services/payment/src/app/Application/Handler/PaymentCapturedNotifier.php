<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Dto\PaymentCompletedMessage;
use App\Domain\Event\PaymentCaptured;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\DistributedBus;

final readonly class PaymentCapturedNotifier
{
    public function __construct(
        private DistributedBus $distributedBus,
    ) {}

    #[EventHandler]
    public function handle(PaymentCaptured $event): void
    {
        $this->distributedBus->convertAndPublishEvent(
            routingKey: 'payment.completed',
            event: new PaymentCompletedMessage(
                paymentId: $event->paymentId->toString(),
                orderId: $event->orderId->toString(),
                amount: $event->amount->amount(),
                currency: $event->amount->currency(),
            ),
        );
    }
}
