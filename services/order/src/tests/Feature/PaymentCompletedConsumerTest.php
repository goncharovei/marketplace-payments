<?php

declare(strict_types=1);

use App\Application\Command\PlaceOrderCommand;
use App\Application\Command\StartPaymentCommand;
use App\Application\Dto\PaymentCompletedMessage;
use App\Application\Handler\PaymentCompletedConsumer;
use App\Domain\Model\Order;
use App\Domain\Model\OrderStatus;
use App\Domain\ValueObject\PaymentId;
use Ecotone\Modelling\CommandBus;

it('marks an order as paid when payment.completed is received', function (): void {
    $orderId = app(CommandBus::class)->send(new PlaceOrderCommand(
        buyerId: 'buyer-paid',
        sellerId: 'seller-paid',
        items: [
            ['productId' => 'product-1', 'quantity' => 1, 'amount' => 1000, 'currency' => 'RUB'],
        ],
    ));

    app(CommandBus::class)->send(new StartPaymentCommand($orderId->toString()));

    app(PaymentCompletedConsumer::class)->handle(new PaymentCompletedMessage(
        paymentId: PaymentId::generate()->toString(),
        orderId: $orderId->toString(),
        amount: 1000,
        currency: 'RUB',
    ));

    $order = $this->em->find(Order::class, $orderId);

    expect($order->status())->toBe(OrderStatus::Paid);
});
