<?php

declare(strict_types=1);

use App\Domain\ValueObject\Money;

it('creates money with amount and currency', function (): void {
    $money = Money::of(1000, 'RUB');

    expect($money->amount())->toBe(1000)
        ->and($money->currency())->toBe('RUB');
});

it('rejects negative amount', function (): void {
    expect(fn () => Money::of(-100, 'RUB'))
        ->toThrow(InvalidArgumentException::class);
});

it('creates zero money', function (): void {
    $money = Money::zero('RUB');

    expect($money->amount())->toBe(0);
});

it('adds two amounts of the same currency', function (): void {
    $sum = Money::of(1000, 'RUB')->add(Money::of(500, 'RUB'));

    expect($sum->amount())->toBe(1500);
});

it('cannot add money of different currencies', function (): void {
    expect(fn () => Money::of(1000, 'RUB')->add(Money::of(500, 'USD')))
        ->toThrow(InvalidArgumentException::class);
});

it('subtracts amounts', function (): void {
    $result = Money::of(1000, 'RUB')->subtract(Money::of(300, 'RUB'));

    expect($result->amount())->toBe(700);
});

it('cannot subtract more than available', function (): void {
    expect(fn () => Money::of(1000, 'RUB')->subtract(Money::of(2000, 'RUB')))
        ->toThrow(InvalidArgumentException::class);
});

it('multiplies by integer', function (): void {
    $result = Money::of(1000, 'RUB')->multiply(3);

    expect($result->amount())->toBe(3000);
});

it('compares amounts of the same currency', function (): void {
    expect(Money::of(1000, 'RUB')->isGreaterThan(Money::of(500, 'RUB')))->toBeTrue()
        ->and(Money::of(500, 'RUB')->isGreaterThan(Money::of(1000, 'RUB')))->toBeFalse();
});

it('checks equality by value', function (): void {
    expect(Money::of(1000, 'RUB')->equals(Money::of(1000, 'RUB')))->toBeTrue()
        ->and(Money::of(1000, 'RUB')->equals(Money::of(1000, 'USD')))->toBeFalse();
});

it('is immutable', function (): void {
    $original = Money::of(1000, 'RUB');
    $original->add(Money::of(500, 'RUB'));

    expect($original->amount())->toBe(1000);
});
