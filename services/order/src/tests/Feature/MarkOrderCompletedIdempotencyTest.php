<?php

declare(strict_types=1);

use App\Application\Command\MarkOrderCompletedCommand;
use App\Application\Command\MarkOrderPaidCommand;
use App\Application\Command\PlaceOrderCommand;
use App\Application\Command\StartPaymentCommand;
use App\Domain\Model\Order;
use App\Domain\Model\OrderStatus;
use Ecotone\Modelling\CommandBus;
use Psr\Log\LoggerInterface;

it('is idempotent — second MarkOrderCompleted is skipped', function (): void {
    $spy = Mockery::spy(LoggerInterface::class);
    $this->app->instance(LoggerInterface::class, $spy);

    $commandBus = app(CommandBus::class);

    $orderId = $commandBus->send(new PlaceOrderCommand(
        buyerId: 'buyer-idem2',
        sellerId: 'seller-idem2',
        items: [
            ['productId' => 'p1', 'quantity' => 1, 'amount' => 1000, 'currency' => 'RUB'],
        ],
    ));

    $commandBus->send(new StartPaymentCommand($orderId->toString()));
    $commandBus->send(new MarkOrderPaidCommand($orderId->toString()));
    $commandBus->send(new MarkOrderCompletedCommand($orderId->toString()));
    $commandBus->send(new MarkOrderCompletedCommand($orderId->toString()));

    $order = $this->em->find(Order::class, $orderId);

    expect($order->status())->toBe(OrderStatus::Completed);

    $spy->shouldHaveReceived('info')
        ->withArgs(fn (string $message): bool => $message === 'Order already completed, MarkOrderCompletedCommand skipped')
        ->once();
});
