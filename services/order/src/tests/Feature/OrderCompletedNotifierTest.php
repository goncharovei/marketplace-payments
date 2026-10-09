<?php

declare(strict_types=1);

use App\Application\Command\MarkOrderPaidCommand;
use App\Application\Command\PlaceOrderCommand;
use App\Application\Command\StartPaymentCommand;
use App\Application\Dto\OrderCompletedMessage;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\DistributedBus;
use Mockery\MockInterface;

it('publishes order.completed after the order transitions to Completed', function (): void {
    /** @var DistributedBus&MockInterface $distributedBus */
    $distributedBus = Mockery::mock(DistributedBus::class);
    $distributedBus->shouldReceive('convertAndPublishEvent')->zeroOrMoreTimes();

    $distributedBus
        ->shouldReceive('convertAndPublishEvent')
        ->once()
        ->withArgs(function (string $routingKey, mixed $event): bool {
            return $routingKey === 'order.completed'
                && $event instanceof OrderCompletedMessage
                && $event->sellerId === 'seller-completed';
        });

    $this->app->instance(DistributedBus::class, $distributedBus);

    $commandBus = app(CommandBus::class);

    $orderId = $commandBus->send(new PlaceOrderCommand(
        buyerId: 'buyer-completed',
        sellerId: 'seller-completed',
        items: [
            ['productId' => 'p1', 'quantity' => 1, 'amount' => 2000, 'currency' => 'RUB'],
        ],
    ));

    $commandBus->send(new StartPaymentCommand($orderId->toString()));
    $commandBus->send(new MarkOrderPaidCommand($orderId->toString()));
});
