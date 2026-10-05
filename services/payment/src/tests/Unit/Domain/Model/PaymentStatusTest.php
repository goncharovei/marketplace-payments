<?php

declare(strict_types=1);

use App\Domain\Model\PaymentStatus;

it('identifies final states', function (): void {
    expect(PaymentStatus::FAILED->isFinal())->toBeTrue()
        ->and(PaymentStatus::REFUNDED->isFinal())->toBeTrue()
        ->and(PaymentStatus::INITIATED->isFinal())->toBeFalse()
        ->and(PaymentStatus::AUTHORIZED->isFinal())->toBeFalse()
        ->and(PaymentStatus::CAPTURED->isFinal())->toBeFalse()
        ->and(PaymentStatus::PARTIALLY_REFUNDED->isFinal())->toBeFalse();
});

it('allows authorization only from Initiated', function (): void {
    expect(PaymentStatus::INITIATED->canBeAuthorized())->toBeTrue()
        ->and(PaymentStatus::AUTHORIZED->canBeAuthorized())->toBeFalse()
        ->and(PaymentStatus::CAPTURED->canBeAuthorized())->toBeFalse();
});

it('allows capture only from Authorized', function (): void {
    expect(PaymentStatus::AUTHORIZED->canBeCaptured())->toBeTrue()
        ->and(PaymentStatus::INITIATED->canBeCaptured())->toBeFalse()
        ->and(PaymentStatus::CAPTURED->canBeCaptured())->toBeFalse();
});

it('allows refund from Captured and PartiallyRefunded', function (): void {
    expect(PaymentStatus::CAPTURED->canBeRefunded())->toBeTrue()
        ->and(PaymentStatus::PARTIALLY_REFUNDED->canBeRefunded())->toBeTrue()
        ->and(PaymentStatus::INITIATED->canBeRefunded())->toBeFalse()
        ->and(PaymentStatus::AUTHORIZED->canBeRefunded())->toBeFalse()
        ->and(PaymentStatus::REFUNDED->canBeRefunded())->toBeFalse();
});
