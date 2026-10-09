<?php

declare(strict_types=1);

use App\Application\Command\CancelOrderCommand;
use App\Application\Command\MarkOrderPaidCommand;
use App\Application\Command\PlaceOrderCommand;
use App\Application\Command\StartPaymentCommand;
use App\Domain\Exception\OrderNotFoundException;
use App\Domain\Model\Order;
use App\Domain\Model\OrderStatus;
use App\Domain\ValueObject\OrderId;
use Ecotone\Modelling\CommandBus;

function placeOrderForLifecycle(): OrderId
{
    return app(CommandBus::class)->send(new PlaceOrderCommand(
        buyerId: 'buyer-1',
        sellerId: 'seller-1',
        items: [
            ['productId' => 'p1', 'quantity' => 1, 'amount' => 1000, 'currency' => 'RUB'],
        ],
    ));
}

it('runs full order lifecycle: created → completed', function (): void {
    $commandBus = app(CommandBus::class);

    $orderId = placeOrderForLifecycle();

    $commandBus->send(new StartPaymentCommand($orderId->toString()));
    $commandBus->send(new MarkOrderPaidCommand($orderId->toString()));

    $order = $this->em->find(Order::class, $orderId);

    expect($order->status())->toBe(OrderStatus::Completed);
});

it('cancels an unpaid order', function (): void {
    $commandBus = app(CommandBus::class);

    $orderId = placeOrderForLifecycle();
    $commandBus->send(new CancelOrderCommand($orderId->toString(), 'Buyer changed mind'));

    $order = $this->em->find(Order::class, $orderId);

    expect($order->status())->toBe(OrderStatus::Cancelled);
});

it('cannot cancel a completed order', function (): void {
    $commandBus = app(CommandBus::class);

    $orderId = placeOrderForLifecycle();
    $commandBus->send(new StartPaymentCommand($orderId->toString()));
    $commandBus->send(new MarkOrderPaidCommand($orderId->toString()));

    expect(fn () => $commandBus->send(new CancelOrderCommand(
        $orderId->toString(),
        'Too late',
    )))->toThrow(DomainException::class);
});

it('throws when starting payment for non-existent order', function (): void {
    $commandBus = app(CommandBus::class);

    expect(fn () => $commandBus->send(new StartPaymentCommand(
        OrderId::generate()->toString(),
    )))->toThrow(OrderNotFoundException::class);
});
