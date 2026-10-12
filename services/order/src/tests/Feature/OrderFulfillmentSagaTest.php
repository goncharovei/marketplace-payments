<?php

declare(strict_types=1);

use App\Application\Command\MarkOrderCompletedCommand;
use App\Application\Command\MarkOrderPaidCommand;
use App\Application\Command\PlaceOrderCommand;
use App\Application\Command\StartPaymentCommand;
use App\Domain\Model\Order;
use App\Domain\Model\OrderStatus;
use Ecotone\Modelling\CommandBus;

it('orchestrates the saga through happy path: created → paid → completed', function (): void {
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

    // After MarkOrderPaid the order stays in Paid — Saga raises
    // PayoutRequested, and the distributed command is sent by a notifier.
    $order = $this->em->find(Order::class, $orderId);
    expect($order->status())->toBe(OrderStatus::Paid);

    // Simulate Payout Service's success response.
    $commandBus->send(new MarkOrderCompletedCommand($orderId->toString()));

    $order = $this->em->find(Order::class, $orderId);
    expect($order->status())->toBe(OrderStatus::Completed);
});
