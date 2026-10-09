<?php

declare(strict_types=1);

use App\Domain\Model\SellerBalance;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\SellerId;

function createBalance(): SellerBalance
{
    return SellerBalance::start(
        SellerId::fromString('seller-1'),
        'RUB',
    );
}

it('starts with zero balances', function (): void {
    $balance = createBalance();

    expect($balance->available()->amount())->toBe(0)
        ->and($balance->reserved()->amount())->toBe(0)
        ->and($balance->debt()->amount())->toBe(0)
        ->and($balance->available()->currency())->toBe('RUB');
});

it('earns money into available', function (): void {
    $balance = createBalance();
    $balance->earn(Money::of(1000, 'RUB'));

    expect($balance->available()->amount())->toBe(1000);
});

it('reserves from available', function (): void {
    $balance = createBalance();
    $balance->earn(Money::of(1000, 'RUB'));
    $balance->reserve(Money::of(400, 'RUB'));

    expect($balance->available()->amount())->toBe(600)
        ->and($balance->reserved()->amount())->toBe(400);
});

it('cannot reserve more than available', function (): void {
    $balance = createBalance();
    $balance->earn(Money::of(500, 'RUB'));

    expect(fn () => $balance->reserve(Money::of(1000, 'RUB')))
        ->toThrow(DomainException::class);
});

it('releases reserved back to available', function (): void {
    $balance = createBalance();
    $balance->earn(Money::of(1000, 'RUB'));
    $balance->reserve(Money::of(400, 'RUB'));

    $balance->release(Money::of(400, 'RUB'));

    expect($balance->available()->amount())->toBe(1000)
        ->and($balance->reserved()->amount())->toBe(0);
});

it('cannot release more than reserved', function (): void {
    $balance = createBalance();
    $balance->earn(Money::of(1000, 'RUB'));
    $balance->reserve(Money::of(400, 'RUB'));

    expect(fn () => $balance->release(Money::of(500, 'RUB')))
        ->toThrow(DomainException::class);
});

it('deducts from reserved on completion', function (): void {
    $balance = createBalance();
    $balance->earn(Money::of(1000, 'RUB'));
    $balance->reserve(Money::of(400, 'RUB'));

    $balance->deduct(Money::of(400, 'RUB'));

    expect($balance->available()->amount())->toBe(600)
        ->and($balance->reserved()->amount())->toBe(0);
});

it('cannot deduct more than reserved', function (): void {
    $balance = createBalance();

    expect(fn () => $balance->deduct(Money::of(100, 'RUB')))
        ->toThrow(DomainException::class);
});

it('increases debt on compensation', function (): void {
    $balance = createBalance();
    $balance->increaseDebt(Money::of(500, 'RUB'));

    expect($balance->debt()->amount())->toBe(500);
});

it('pays off debt', function (): void {
    $balance = createBalance();
    $balance->increaseDebt(Money::of(500, 'RUB'));
    $balance->payDebt(Money::of(300, 'RUB'));

    expect($balance->debt()->amount())->toBe(200);
});

it('cannot pay more than debt', function (): void {
    $balance = createBalance();
    $balance->increaseDebt(Money::of(100, 'RUB'));

    expect(fn () => $balance->payDebt(Money::of(200, 'RUB')))
        ->toThrow(DomainException::class);
});

it('rejects non-positive amounts', function (): void {
    $balance = createBalance();

    expect(fn () => $balance->earn(Money::of(0, 'RUB')))
        ->toThrow(DomainException::class)
        ->and(fn () => $balance->reserve(Money::of(0, 'RUB')))
        ->toThrow(DomainException::class)
        ->and(fn () => $balance->increaseDebt(Money::of(0, 'RUB')))
        ->toThrow(DomainException::class);
});
