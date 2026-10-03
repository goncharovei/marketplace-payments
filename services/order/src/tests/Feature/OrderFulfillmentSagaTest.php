<?php

declare(strict_types=1);

use App\Application\Command\CancelOrderCommand;
use App\Application\Command\MarkOrderPaidCommand;
use App\Application\Command\PlaceOrderCommand;
use App\Application\Command\StartPaymentCommand;
use App\Domain\Model\Order;
use App\Domain\Model\OrderStatus;
use Ecotone\Modelling\CommandBus;

it('runs saga through happy path: created → paid', function (): void {
    $commandBus = app(CommandBus::class);

    $orderId = $commandBus->send(new PlaceOrderCommand(
        buyerId: 'saga-buyer',
        sellerId: 'saga-seller',
        items: [
            ['productId' => 'p1', 'quantity' => 1, 'amount' => 1000, 'currency' => 'RUB'],
        ],
    ));

    $commandBus->send(new StartPaymentCommand($orderId->toString()));
    $commandBus->send(new MarkOrderPaidCommand($orderId->toString()));

    $order = $this->em->find(Order::class, $orderId);

    expect($order->status())->toBe(OrderStatus::Paid);
});

it('cancels saga when order is cancelled', function (): void {
    $commandBus = app(CommandBus::class);

    $orderId = $commandBus->send(new PlaceOrderCommand(
        buyerId: 'saga-buyer-2',
        sellerId: 'saga-seller-2',
        items: [
            ['productId' => 'p1', 'quantity' => 1, 'amount' => 1000, 'currency' => 'RUB'],
        ],
    ));

    $commandBus->send(new CancelOrderCommand(
        orderId: $orderId->toString(),
        reason: 'Test cancellation',
    ));

    $order = $this->em->find(Order::class, $orderId);

    expect($order->status())->toBe(OrderStatus::Cancelled);
});
