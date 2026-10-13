<?php

declare(strict_types=1);

use App\Application\Command\MarkOrderPaidCommand;
use App\Application\Command\PlaceOrderCommand;
use App\Application\Command\StartPaymentCommand;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\DistributedBus;
use Mockery\MockInterface;
use Shared\Contracts\CreatePayoutMessage;

it('sends payout.create command to payout_service when order is paid', function (): void {
    /** @var DistributedBus&MockInterface $distributedBus */
    $distributedBus = Mockery::mock(DistributedBus::class);

    // Allow any distributed events (order.created, payment.completed, etc.)
    $distributedBus->shouldReceive('convertAndPublishEvent')->zeroOrMoreTimes();

    // Narrow rule FIRST: payout.create must be sent exactly once with the right payload.
    $distributedBus
        ->shouldReceive('convertAndSendCommand')
        ->once()
        ->withArgs(function (string $target, string $routingKey, mixed $command): bool {
            return $target === 'payout_service'
                && $routingKey === 'payout.create'
                && $command instanceof CreatePayoutMessage
                && $command->amount === 1000;
        });

    // Catch-all for other distributed commands (e.g. payment.capture from
    // OrderPaymentStartedNotifier, which fires during StartPaymentCommand).
    // Placed AFTER the narrow rule so Mockery matches the specific one first.
    $distributedBus
        ->shouldReceive('convertAndSendCommand')
        ->withArgs(fn (string $target, string $routingKey): bool => $routingKey !== 'payout.create')
        ->zeroOrMoreTimes();

    $this->app->instance(DistributedBus::class, $distributedBus);

    $commandBus = app(CommandBus::class);

    $orderId = $commandBus->send(new PlaceOrderCommand(
        buyerId: 'payout-requested-buyer',
        sellerId: 'payout-requested-seller',
        items: [
            ['productId' => 'p1', 'quantity' => 1, 'amount' => 1000, 'currency' => 'RUB'],
        ],
    ));

    $commandBus->send(new StartPaymentCommand($orderId->toString()));
    $commandBus->send(new MarkOrderPaidCommand($orderId->toString()));
});
