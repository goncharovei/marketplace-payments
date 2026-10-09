<?php

declare(strict_types=1);

use App\Domain\Event\OrderCancelled;
use App\Domain\Event\OrderCreated;
use App\Domain\Event\OrderPaid;
use App\Domain\Event\OrderRefunded;
use App\Domain\Event\PaymentStarted;
use App\Domain\Model\Order;
use App\Domain\Model\OrderItem;
use App\Domain\Model\OrderItemCollection;
use App\Domain\Model\OrderStatus;
use App\Domain\ValueObject\BuyerId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\ProductId;
use App\Domain\ValueObject\SellerId;

function createTestItems(): OrderItemCollection
{
    return OrderItemCollection::fromArray(
        OrderItem::create(ProductId::fromString('product-1'), 2, Money::of(500, 'RUB')),
        OrderItem::create(ProductId::fromString('product-2'), 1, Money::of(1500, 'RUB')),
    );
}

function placeTestOrder(): Order
{
    return Order::place(
        OrderId::generate(),
        BuyerId::fromString('buyer-1'),
        SellerId::fromString('seller-1'),
        createTestItems(),
    );
}

it('marks order as completed and records OrderCompleted', function (): void {
    $order = placeTestOrder();
    $order->startPayment();
    $order->markAsPaid();
    $order->releaseEvents();

    $order->markAsCompleted();

    expect($order->status())->toBe(OrderStatus::Completed);

    $events = $order->releaseEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(\App\Domain\Event\OrderCompleted::class);
});

it('cannot mark as completed without prior payment', function (): void {
    $order = placeTestOrder();

    expect(fn () => $order->markAsCompleted())->toThrow(DomainException::class);
});

it('places an order and records OrderCreated', function (): void {
    $order = placeTestOrder();

    expect($order->status())->toBe(OrderStatus::Created)
        ->and($order->totalAmount()->amount())->toBe(2500);

    $events = $order->releaseEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(OrderCreated::class);
});

it('rejects placing an order with no items', function (): void {
    expect(fn () => Order::place(
        OrderId::generate(),
        BuyerId::fromString('buyer-1'),
        SellerId::fromString('seller-1'),
        OrderItemCollection::empty(),
    ))->toThrow(DomainException::class);
});

it('starts payment and records PaymentStarted', function (): void {
    $order = placeTestOrder();
    $order->releaseEvents();

    $order->startPayment();

    expect($order->status())->toBe(OrderStatus::PaymentProcessing);

    $events = $order->releaseEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PaymentStarted::class);
});

it('marks order as paid', function (): void {
    $order = placeTestOrder();
    $order->startPayment();
    $order->releaseEvents();

    $order->markAsPaid();

    expect($order->status())->toBe(OrderStatus::Paid);

    $events = $order->releaseEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(OrderPaid::class);
});

it('cannot mark as paid without starting payment', function (): void {
    $order = placeTestOrder();

    expect(fn () => $order->markAsPaid())->toThrow(DomainException::class);
});

it('cannot start payment twice', function (): void {
    $order = placeTestOrder();
    $order->startPayment();

    expect(fn () => $order->startPayment())->toThrow(DomainException::class);
});

it('cancels a created order and records OrderCancelled', function (): void {
    $order = placeTestOrder();
    $order->releaseEvents();

    $order->cancel('Buyer changed their mind');

    expect($order->status())->toBe(OrderStatus::Cancelled);

    $events = $order->releaseEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(OrderCancelled::class);
});

it('refunds a paid order on cancel', function (): void {
    $order = placeTestOrder();
    $order->startPayment();
    $order->markAsPaid();
    $order->releaseEvents();

    $order->cancel('Out of stock');

    expect($order->status())->toBe(OrderStatus::Refunded);

    $events = $order->releaseEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(OrderRefunded::class);
});

it('cannot cancel an already cancelled order', function (): void {
    $order = placeTestOrder();
    $order->cancel('first');

    expect(fn () => $order->cancel('second'))->toThrow(DomainException::class);
});

it('releases events only once', function (): void {
    $order = placeTestOrder();

    $first = $order->releaseEvents();
    $second = $order->releaseEvents();

    expect($first)->toHaveCount(1)
        ->and($second)->toBe([]);
});
