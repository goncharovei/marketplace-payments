<?php

declare(strict_types=1);

use App\Domain\ValueObject\SellerId;

it('creates from non-empty string', function (): void {
    $id = SellerId::fromString('seller-1');

    expect($id->toString())->toBe('seller-1');
});

it('rejects empty string', function (): void {
    expect(fn () => SellerId::fromString(''))
        ->toThrow(InvalidArgumentException::class);
});

it('checks equality by value', function (): void {
    expect(SellerId::fromString('seller-1')->equals(SellerId::fromString('seller-1')))->toBeTrue()
        ->and(SellerId::fromString('seller-1')->equals(SellerId::fromString('seller-2')))->toBeFalse();
});
