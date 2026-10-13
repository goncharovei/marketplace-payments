<?php

declare(strict_types=1);

use App\Application\Command\MarkOrderCompletedCommand;
use App\Application\Command\MarkOrderPaidCommand;
use App\Application\Command\PlaceOrderCommand;
use App\Application\Command\StartPaymentCommand;
use App\Application\Handler\PayoutFailedConsumer;
use App\Domain\Model\Order;
use App\Domain\Model\OrderStatus;
use Ecotone\Modelling\CommandBus;
use Shared\Contracts\PayoutFailedMessage;

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

it('transitions the saga to payout_failed when a payout.failed signal arrives', function (): void {
    $commandBus = app(CommandBus::class);

    $orderId = $commandBus->send(new PlaceOrderCommand(
        buyerId: 'saga-comp-buyer',
        sellerId: 'saga-comp-seller',
        items: [
            ['productId' => 'p1', 'quantity' => 1, 'amount' => 1000, 'currency' => 'RUB'],
        ],
    ));

    $commandBus->send(new StartPaymentCommand($orderId->toString()));
    $commandBus->send(new MarkOrderPaidCommand($orderId->toString()));

    // Simulate payout.failed delivered to the consumer.
    app(PayoutFailedConsumer::class)->handle(new PayoutFailedMessage(
        payoutId: 'payout-comp-1',
        orderId: $orderId->toString(),
        sellerId: 'saga-comp-seller',
        reason: 'Bank outage',
    ));

    $state = $this->em->getConnection()->fetchOne(
        "SELECT document->>'state' FROM ecotone_document_store WHERE document->>'orderId' = ?",
        [$orderId->toString()],
    );

    expect($state)->toBe('payout_failed');

    // The order itself stays Paid — the buyer's money is not refunded.
    $order = $this->em->find(Order::class, $orderId);
    expect($order->status())->toBe(OrderStatus::Paid);
});
