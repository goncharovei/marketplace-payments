<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Dto\CapturePaymentMessage;
use App\Domain\Event\PaymentStarted;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\DistributedBus;

final readonly class OrderPaymentStartedNotifier
{
    public function __construct(
        private DistributedBus $distributedBus,
    ) {}

    #[EventHandler]
    public function handle(PaymentStarted $event): void
    {
        $this->distributedBus->convertAndSendCommand(
            targetServiceName: 'payment_service',
            routingKey: 'payment.capture',
            command: new CapturePaymentMessage(
                orderId: $event->orderId->toString(),
                amount: $event->amount->amount(),
                currency: $event->amount->currency(),
            ),
        );
    }
}
