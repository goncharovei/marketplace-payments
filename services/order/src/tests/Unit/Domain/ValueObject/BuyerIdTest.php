<?php

declare(strict_types=1);

use App\Domain\ValueObject\BuyerId;

it('creates from non-empty string', function (): void {
    $id = BuyerId::fromString('buyer-1');

    expect($id->toString())->toBe('buyer-1');
});

it('rejects empty string', function (): void {
    expect(fn () => BuyerId::fromString(''))
        ->toThrow(InvalidArgumentException::class);
});

it('checks equality by value', function (): void {
    expect(BuyerId::fromString('buyer-1')->equals(BuyerId::fromString('buyer-1')))->toBeTrue()
        ->and(BuyerId::fromString('buyer-1')->equals(BuyerId::fromString('buyer-2')))->toBeFalse();
});
