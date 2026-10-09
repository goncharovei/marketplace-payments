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

    // PaymentStarted notifier sends a distributed command — allow it.
    $distributedBus->shouldReceive('convertAndSendCommand')->zeroOrMoreTimes();

    // Narrow rule FIRST: order.completed must be published exactly once.
    $distributedBus
        ->shouldReceive('convertAndPublishEvent')
        ->once()
        ->withArgs(function (string $routingKey, mixed $event): bool {
            return $routingKey === 'order.completed'
                && $event instanceof OrderCompletedMessage
                && $event->sellerId === 'seller-completed';
        });

    // Catch-all rule for other events (order.created, etc.).
    // Placed AFTER the narrow rule so Mockery matches the specific one first.
    $distributedBus
        ->shouldReceive('convertAndPublishEvent')
        ->withArgs(fn (string $routingKey): bool => $routingKey !== 'order.completed')
        ->zeroOrMoreTimes();

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
