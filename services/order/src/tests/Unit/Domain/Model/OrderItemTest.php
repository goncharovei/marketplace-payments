<?php

declare(strict_types=1);

use App\Domain\Model\OrderItem;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductId;

it('creates a valid item', function (): void {
    $item = OrderItem::create(
        ProductId::fromString('product-1'),
        2,
        Money::of(500, 'RUB'),
    );

    expect($item->quantity())->toBe(2)
        ->and($item->subtotal()->amount())->toBe(1000);
});

it('rejects zero or negative quantity', function (): void {
    expect(fn () => OrderItem::create(ProductId::fromString('p1'), 0, Money::of(100, 'RUB')))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => OrderItem::create(ProductId::fromString('p1'), -1, Money::of(100, 'RUB')))
        ->toThrow(InvalidArgumentException::class);
});

it('changes quantity', function (): void {
    $item = OrderItem::create(ProductId::fromString('p1'), 2, Money::of(500, 'RUB'));
    $item->changeQuantity(5);

    expect($item->quantity())->toBe(5)
        ->and($item->subtotal()->amount())->toBe(2500);
});

it('rejects invalid quantity change', function (): void {
    $item = OrderItem::create(ProductId::fromString('p1'), 2, Money::of(500, 'RUB'));

    expect(fn () => $item->changeQuantity(0))
        ->toThrow(InvalidArgumentException::class);
});

it('compares items by product id', function (): void {
    $a = OrderItem::create(ProductId::fromString('p1'), 1, Money::of(100, 'RUB'));
    $b = OrderItem::create(ProductId::fromString('p1'), 5, Money::of(999, 'RUB'));
    $c = OrderItem::create(ProductId::fromString('p2'), 1, Money::of(100, 'RUB'));

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
