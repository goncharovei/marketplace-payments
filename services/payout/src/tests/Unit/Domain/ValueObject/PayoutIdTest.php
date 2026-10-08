<?php

declare(strict_types=1);

use App\Domain\ValueObject\PayoutId;

it('generates a valid uuid', function (): void {
    $id = PayoutId::generate();

    expect($id->toString())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
});

it('creates from valid string', function (): void {
    $uuid = '550e8400-e29b-41d4-a716-446655440000';
    $id = PayoutId::fromString($uuid);

    expect($id->toString())->toBe($uuid);
});

it('rejects invalid uuid', function (): void {
    expect(fn () => PayoutId::fromString('not-a-uuid'))
        ->toThrow(InvalidArgumentException::class);
});

it('checks equality by value', function (): void {
    $uuid = '550e8400-e29b-41d4-a716-446655440000';

    expect(PayoutId::fromString($uuid)->equals(PayoutId::fromString($uuid)))->toBeTrue()
        ->and(PayoutId::generate()->equals(PayoutId::generate()))->toBeFalse();
});
