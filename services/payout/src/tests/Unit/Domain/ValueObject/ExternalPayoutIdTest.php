<?php

declare(strict_types=1);

use App\Domain\ValueObject\ExternalPayoutId;

it('creates from non-empty string', function (): void {
    $id = ExternalPayoutId::fromString('ext-payout-1');

    expect($id->toString())->toBe('ext-payout-1');
});

it('rejects empty string', function (): void {
    expect(fn () => ExternalPayoutId::fromString(''))
        ->toThrow(InvalidArgumentException::class);
});

it('checks equality by value', function (): void {
    expect(ExternalPayoutId::fromString('a')->equals(ExternalPayoutId::fromString('a')))->toBeTrue()
        ->and(ExternalPayoutId::fromString('a')->equals(ExternalPayoutId::fromString('b')))->toBeFalse();
});
