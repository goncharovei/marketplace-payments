<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\StartPaymentCommand;
use App\Domain\Event\OrderCreated;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\CommandBus;

final readonly class StartPaymentOnOrderCreatedHandler
{
    public function __construct(
        private CommandBus $commandBus,
    ) {}

    #[EventHandler]
    public function handle(OrderCreated $event): void
    {
        $this->commandBus->send(new StartPaymentCommand(
            orderId: $event->orderId->toString(),
        ));
    }
}
