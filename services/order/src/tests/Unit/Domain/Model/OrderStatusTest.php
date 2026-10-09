<?php

declare(strict_types=1);

use App\Domain\Model\OrderStatus;

it('identifies final states', function (): void {
    expect(OrderStatus::Completed->isFinal())->toBeTrue()
        ->and(OrderStatus::Cancelled->isFinal())->toBeTrue()
        ->and(OrderStatus::Refunded->isFinal())->toBeTrue()
        ->and(OrderStatus::Created->isFinal())->toBeFalse()
        ->and(OrderStatus::PaymentProcessing->isFinal())->toBeFalse()
        ->and(OrderStatus::Paid->isFinal())->toBeFalse();
});

it('allows completion only from Paid', function (): void {
    expect(OrderStatus::Paid->canBeCompleted())->toBeTrue()
        ->and(OrderStatus::Created->canBeCompleted())->toBeFalse()
        ->and(OrderStatus::PaymentProcessing->canBeCompleted())->toBeFalse()
        ->and(OrderStatus::Completed->canBeCompleted())->toBeFalse();
});

it('allows payment only from Created state', function (): void {
    expect(OrderStatus::Created->canBePaid())->toBeTrue()
        ->and(OrderStatus::Paid->canBePaid())->toBeFalse()
        ->and(OrderStatus::Cancelled->canBePaid())->toBeFalse();
});

it('allows cancellation from Created, Processing and Paid states', function (): void {
    expect(OrderStatus::Created->canBeCancelled())->toBeTrue()
        ->and(OrderStatus::PaymentProcessing->canBeCancelled())->toBeTrue()
        ->and(OrderStatus::Paid->canBeCancelled())->toBeTrue()
        ->and(OrderStatus::Cancelled->canBeCancelled())->toBeFalse()
        ->and(OrderStatus::Refunded->canBeCancelled())->toBeFalse();
});
