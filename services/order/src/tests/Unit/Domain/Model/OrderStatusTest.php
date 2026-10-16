<?php

declare(strict_types=1);

use App\Domain\Model\OrderStatus;

it('identifies final states', function (): void {
    expect(OrderStatus::COMPLETED->isFinal())->toBeTrue()
        ->and(OrderStatus::CANCELLED->isFinal())->toBeTrue()
        ->and(OrderStatus::REFUNDED->isFinal())->toBeTrue()
        ->and(OrderStatus::CREATED->isFinal())->toBeFalse()
        ->and(OrderStatus::PAYMENT_PROCESSING->isFinal())->toBeFalse()
        ->and(OrderStatus::PAID->isFinal())->toBeFalse();
});

it('allows completion only from Paid', function (): void {
    expect(OrderStatus::PAID->canBeCompleted())->toBeTrue()
        ->and(OrderStatus::CREATED->canBeCompleted())->toBeFalse()
        ->and(OrderStatus::PAYMENT_PROCESSING->canBeCompleted())->toBeFalse()
        ->and(OrderStatus::COMPLETED->canBeCompleted())->toBeFalse();
});

it('allows payment only from Created state', function (): void {
    expect(OrderStatus::CREATED->canBePaid())->toBeTrue()
        ->and(OrderStatus::PAID->canBePaid())->toBeFalse()
        ->and(OrderStatus::CANCELLED->canBePaid())->toBeFalse();
});

it('allows cancellation from Created, Processing and Paid states', function (): void {
    expect(OrderStatus::CREATED->canBeCancelled())->toBeTrue()
        ->and(OrderStatus::PAYMENT_PROCESSING->canBeCancelled())->toBeTrue()
        ->and(OrderStatus::PAID->canBeCancelled())->toBeTrue()
        ->and(OrderStatus::CANCELLED->canBeCancelled())->toBeFalse()
        ->and(OrderStatus::REFUNDED->canBeCancelled())->toBeFalse();
});
