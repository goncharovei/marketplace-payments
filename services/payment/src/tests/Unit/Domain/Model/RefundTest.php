<?php

declare(strict_types=1);

use App\Domain\Model\Refund;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\RefundId;

it('creates a valid refund', function (): void {
    $refund = Refund::create(
        RefundId::generate(),
        Money::of(500, 'RUB'),
        'Customer request',
    );

    expect($refund->amount()->amount())->toBe(500)
        ->and($refund->amount()->currency())->toBe('RUB')
        ->and($refund->reason())->toBe('Customer request');
});

it('rejects empty reason', function (): void {
    expect(fn () => Refund::create(
        RefundId::generate(),
        Money::of(500, 'RUB'),
        '   ',
    ))->toThrow(InvalidArgumentException::class);
});
