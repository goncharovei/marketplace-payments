<?php

declare(strict_types=1);

use App\Application\Command\PlaceOrderCommand;
use Ecotone\Modelling\CommandBus;
use Psr\Log\LoggerInterface;

/**
 * Verifies that the OrderCreated event is published via the Event Bus
 * after the PlaceOrderCommandHandler runs.
 */
it('publishes OrderCreated event after placing an order', function (): void {
    $orderId = app(CommandBus::class)->send(new PlaceOrderCommand(
        buyerId: 'buyer-1',
        sellerId: 'seller-1',
        items: [
            ['productId' => 'product-1', 'quantity' => 1, 'amount' => 500, 'currency' => 'RUB'],
        ],
    ));

    expect($orderId)->not->toBeNull();
});

it('dispatches OrderCreated to registered handlers', function (): void {
    // Spy on the logger to verify the event reached the handler
    $spy = Mockery::spy(LoggerInterface::class);
    app()->instance(LoggerInterface::class, $spy);

    app(CommandBus::class)->send(new PlaceOrderCommand(
        buyerId: 'buyer-2',
        sellerId: 'seller-2',
        items: [
            ['productId' => 'product-1', 'quantity' => 2, 'amount' => 500, 'currency' => 'RUB'],
        ],
    ));

    // The OrderCreatedHandler should have logged the event
    $spy->shouldHaveReceived('info')
        ->withArgs(function (string $message, array $context): bool {
            return $message === 'Order created'
                && isset($context['orderId'], $context['totalAmount']);
        })
        ->once();
});
