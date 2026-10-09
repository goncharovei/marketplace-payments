<?php

declare(strict_types=1);

use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\PlatformFee;

it('creates from basis points', function (): void {
    $fee = PlatformFee::fromBasisPoints(500);

    expect($fee->basisPoints())->toBe(500);
});

it('creates from percent', function (): void {
    $fee = PlatformFee::fromPercent(10.0);

    expect($fee->basisPoints())->toBe(1000);
});

it('rejects out-of-range basis points', function (): void {
    expect(fn () => PlatformFee::fromBasisPoints(-1))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => PlatformFee::fromBasisPoints(10001))
        ->toThrow(InvalidArgumentException::class);
});

it('calculates 10 percent fee', function (): void {
    $fee = PlatformFee::tenPercent();
    $amount = Money::of(2500, 'RUB');

    expect($fee->calculate($amount)->amount())->toBe(250)
        ->and($fee->calculate($amount)->currency())->toBe('RUB');
});

it('calculates 5 percent fee', function (): void {
    $fee = PlatformFee::fromPercent(5.0);
    $amount = Money::of(1000, 'RUB');

    expect($fee->calculate($amount)->amount())->toBe(50);
});

it('checks equality by value', function (): void {
    expect(PlatformFee::fromBasisPoints(1000)->equals(PlatformFee::fromBasisPoints(1000)))->toBeTrue()
        ->and(PlatformFee::fromBasisPoints(1000)->equals(PlatformFee::fromBasisPoints(500)))->toBeFalse();
});
