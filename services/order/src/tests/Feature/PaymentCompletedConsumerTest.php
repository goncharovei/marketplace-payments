<?php

declare(strict_types=1);

use App\Application\Command\PlaceOrderCommand;
use App\Application\Command\StartPaymentCommand;
use App\Application\Dto\PaymentCompletedMessage;
use App\Application\Handler\PaymentCompletedConsumer;
use App\Domain\Model\Order;
use App\Domain\Model\OrderStatus;
use Ecotone\Modelling\CommandBus;

it('marks an order as completed when payment.completed is received', function (): void {
    $orderId = app(CommandBus::class)->send(new PlaceOrderCommand(
        buyerId: 'buyer-paid',
        sellerId: 'seller-paid',
        items: [
            ['productId' => 'product-1', 'quantity' => 1, 'amount' => 1000, 'currency' => 'RUB'],
        ],
    ));

    app(CommandBus::class)->send(new StartPaymentCommand($orderId->toString()));

    app(PaymentCompletedConsumer::class)->handle(new PaymentCompletedMessage(
        paymentId: 'test-payment-id',
        orderId: $orderId->toString(),
        amount: 1000,
        currency: 'RUB',
    ));

    $order = $this->em->find(Order::class, $orderId);

    // MarkOrderPaidCommandHandler auto-completes the order for the demo
    // (delivery is instantaneous). In production, a delivery step would
    // sit between Paid and Completed.
    expect($order->status())->toBe(OrderStatus::Completed);
});
