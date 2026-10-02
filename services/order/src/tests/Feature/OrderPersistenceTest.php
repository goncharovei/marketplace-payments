<?php

declare(strict_types=1);

use App\Domain\Model\Order;
use App\Domain\Model\OrderItem;
use App\Domain\Model\OrderItemCollection;
use App\Domain\Model\OrderStatus;
use App\Domain\ValueObject\BuyerId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\ProductId;
use App\Domain\ValueObject\SellerId;

/**
 * Integration tests for Order persistence via Doctrine ORM.
 *
 * The EntityManager is initialized and each test is wrapped in a database
 * transaction by the Pest hooks defined in tests/Pest.php.
 */
function makeOrder(): Order
{
    return Order::place(
        OrderId::generate(),
        BuyerId::fromString('buyer-1'),
        SellerId::fromString('seller-1'),
        OrderItemCollection::fromArray(
            OrderItem::create(ProductId::fromString('product-1'), 2, Money::of(500, 'RUB')),
            OrderItem::create(ProductId::fromString('product-2'), 1, Money::of(1500, 'RUB')),
        ),
    );
}

it('persists and reloads an order with all value objects intact', function (): void {
    $order = makeOrder();
    $orderId = $order->id();
    $expectedTotal = $order->totalAmount()->amount();

    $this->em->persist($order);
    $this->em->flush();
    $this->em->clear();

    $reloaded = $this->em->find(Order::class, $orderId);

    expect($reloaded)->not->toBeNull()
        ->and($reloaded->id()->equals($orderId))->toBeTrue()
        ->and($reloaded->status())->toBe(OrderStatus::Created)
        ->and($reloaded->totalAmount()->amount())->toBe($expectedTotal)
        ->and($reloaded->totalAmount()->currency())->toBe('RUB')
        ->and($reloaded->items()->count())->toBe(2);
});

it('persists status changes', function (): void {
    $order = makeOrder();
    $orderId = $order->id();

    $order->startPayment();
    $order->markAsPaid();

    $this->em->persist($order);
    $this->em->flush();
    $this->em->clear();

    $reloaded = $this->em->find(Order::class, $orderId);

    expect($reloaded->status())->toBe(OrderStatus::Paid);
});

it('preserves item details after reload', function (): void {
    $uniqueProductId = 'unique-product-1';

    $order = Order::place(
        OrderId::generate(),
        BuyerId::fromString('buyer-2'),
        SellerId::fromString('seller-2'),
        OrderItemCollection::fromArray(
            OrderItem::create(ProductId::fromString($uniqueProductId), 3, Money::of(750, 'RUB')),
        ),
    );
    $orderId = $order->id();

    $this->em->persist($order);
    $this->em->flush();
    $this->em->clear();

    $reloaded = $this->em->find(Order::class, $orderId);
    $items = iterator_to_array($reloaded->items());

    expect($items)->toHaveCount(1)
        ->and($items[0]->productId()->toString())->toBe($uniqueProductId)
        ->and($items[0]->quantity())->toBe(3)
        ->and($items[0]->price()->amount())->toBe(750)
        ->and($items[0]->subtotal()->amount())->toBe(2250);
});
