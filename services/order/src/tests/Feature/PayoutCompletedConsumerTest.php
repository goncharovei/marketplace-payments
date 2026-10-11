<?php

declare(strict_types=1);

use App\Application\Command\MarkOrderPaidCommand;
use App\Application\Command\PlaceOrderCommand;
use App\Application\Command\StartPaymentCommand;
use App\Application\Dto\PayoutCompletedMessage;
use App\Application\Handler\PayoutCompletedConsumer;
use App\Domain\Model\Order;
use App\Domain\Model\OrderStatus;
use Ecotone\Modelling\CommandBus;
use Psr\Log\LoggerInterface;

it('marks order as completed and logs when payout.completed is received', function (): void {
    $spy = Mockery::spy(LoggerInterface::class);
    $this->app->instance(LoggerInterface::class, $spy);

    $commandBus = app(CommandBus::class);

    // Arrange: create an order and bring it to Paid.
    $orderId = $commandBus->send(new PlaceOrderCommand(
        buyerId: 'buyer-payout-completed',
        sellerId: 'seller-payout-completed',
        items: [
            ['productId' => 'p1', 'quantity' => 1, 'amount' => 1000, 'currency' => 'RUB'],
        ],
    ));

    $commandBus->send(new StartPaymentCommand($orderId->toString()));
    $commandBus->send(new MarkOrderPaidCommand($orderId->toString()));

    // Act: simulate the payout.completed event from Payout Service.
    app(PayoutCompletedConsumer::class)->handle(new PayoutCompletedMessage(
        payoutId: 'payout-1',
        orderId: $orderId->toString(),
        sellerId: 'seller-payout-completed',
        amount: 900,
        currency: 'RUB',
    ));

    // Assert: log was written.
    $spy->shouldHaveReceived('info')
        ->withArgs(function (string $message, array $context) use ($orderId): bool {
            return $message === 'Payout completed for order'
                && $context['orderId'] === $orderId->toString()
                && $context['payoutId'] === 'payout-1';
        })
        ->once();

    // Assert: order transitioned to Completed.
    $order = $this->em->find(Order::class, $orderId);

    expect($order->status())->toBe(OrderStatus::Completed);
});
