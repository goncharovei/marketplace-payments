<?php

declare(strict_types=1);

use App\Application\Command\PlaceOrderCommand;
use App\Domain\Model\Order;
use App\Domain\Model\OrderStatus;
use App\Domain\ValueObject\OrderId;
use Ecotone\Modelling\CommandBus;

/**
 * Tests the PlaceOrderCommand via the Ecotone CommandBus.
 * Verifies that the command handler creates and persists an Order.
 */
it('creates a new order via the command bus', function (): void {
    $commandBus = app(CommandBus::class);

    $orderId = $commandBus->send(new PlaceOrderCommand(
        buyerId: 'buyer-1',
        sellerId: 'seller-1',
        items: [
            ['productId' => 'product-1', 'quantity' => 2, 'amount' => 500, 'currency' => 'RUB'],
            ['productId' => 'product-2', 'quantity' => 1, 'amount' => 1500, 'currency' => 'RUB'],
        ],
    ));

    expect($orderId)->toBeInstanceOf(OrderId::class);

    $order = $this->em->find(Order::class, $orderId);

    expect($order)->not->toBeNull()
        ->and($order->status())->toBe(OrderStatus::Created)
        ->and($order->totalAmount()->amount())->toBe(2500)
        ->and($order->items()->count())->toBe(2);
});

it('rejects an order with no items', function (): void {
    $commandBus = app(CommandBus::class);

    expect(fn () => $commandBus->send(new PlaceOrderCommand(
        buyerId: 'buyer-1',
        sellerId: 'seller-1',
        items: [],
    )))->toThrow(DomainException::class);
});
