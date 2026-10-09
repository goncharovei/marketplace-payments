<?php

declare(strict_types=1);

use App\Domain\Model\PayoutStatus;

it('identifies the final state', function (): void {
    expect(PayoutStatus::COMPLETED->isFinal())->toBeTrue()
        ->and(PayoutStatus::PENDING->isFinal())->toBeFalse()
        ->and(PayoutStatus::PROCESSING->isFinal())->toBeFalse()
        ->and(PayoutStatus::FAILED->isFinal())->toBeFalse()
        ->and(PayoutStatus::ON_HOLD->isFinal())->toBeFalse();
});

it('allows start only from Pending', function (): void {
    expect(PayoutStatus::PENDING->canBeStarted())->toBeTrue()
        ->and(PayoutStatus::PROCESSING->canBeStarted())->toBeFalse()
        ->and(PayoutStatus::FAILED->canBeStarted())->toBeFalse();
});

it('allows complete only from Processing', function (): void {
    expect(PayoutStatus::PROCESSING->canBeCompleted())->toBeTrue()
        ->and(PayoutStatus::PENDING->canBeCompleted())->toBeFalse()
        ->and(PayoutStatus::COMPLETED->canBeCompleted())->toBeFalse();
});

it('allows fail only from Processing', function (): void {
    expect(PayoutStatus::PROCESSING->canBeFailed())->toBeTrue()
        ->and(PayoutStatus::PENDING->canBeFailed())->toBeFalse()
        ->and(PayoutStatus::COMPLETED->canBeFailed())->toBeFalse();
});

it('allows retry only from Failed', function (): void {
    expect(PayoutStatus::FAILED->canBeRetried())->toBeTrue()
        ->and(PayoutStatus::PROCESSING->canBeRetried())->toBeFalse();
});

it('allows escalation only from Failed', function (): void {
    expect(PayoutStatus::FAILED->canBeEscalated())->toBeTrue()
        ->and(PayoutStatus::PROCESSING->canBeEscalated())->toBeFalse();
});
