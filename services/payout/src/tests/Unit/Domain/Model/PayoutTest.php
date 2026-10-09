<?php

declare(strict_types=1);

use App\Domain\Event\PayoutCompleted;
use App\Domain\Event\PayoutCreated;
use App\Domain\Event\PayoutEscalated;
use App\Domain\Event\PayoutFailed;
use App\Domain\Model\Payout;
use App\Domain\Model\PayoutMethod;
use App\Domain\Model\PayoutStatus;
use App\Domain\ValueObject\ExternalPayoutId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PayoutId;
use App\Domain\ValueObject\PlatformFee;
use App\Domain\ValueObject\SellerId;

function createTestPayout(int $orderAmount = 2500): Payout
{
    return Payout::create(
        PayoutId::generate(),
        SellerId::fromString('seller-1'),
        OrderId::generate(),
        Money::of($orderAmount, 'RUB'),
        PlatformFee::tenPercent(),
        PayoutMethod::BANK_TRANSFER,
    );
}

it('creates a payout and records PayoutCreated', function (): void {
    $payout = createTestPayout(2500);

    expect($payout->status())->toBe(PayoutStatus::PENDING)
        ->and($payout->amount()->amount())->toBe(2250)
        ->and($payout->platformFee()->amount())->toBe(250)
        ->and($payout->amount()->currency())->toBe('RUB')
        ->and($payout->retryCount())->toBe(0)
        ->and($payout->externalId())->toBeNull();

    $events = $payout->releaseEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PayoutCreated::class);
});

it('rejects creating a payout with non-positive order amount', function (): void {
    expect(fn () => Payout::create(
        PayoutId::generate(),
        SellerId::fromString('seller-1'),
        OrderId::generate(),
        Money::of(0, 'RUB'),
        PlatformFee::tenPercent(),
        PayoutMethod::BANK_TRANSFER,
    ))->toThrow(DomainException::class);
});

it('starts a payout from Pending', function (): void {
    $payout = createTestPayout();
    $payout->releaseEvents();

    $payout->start();

    expect($payout->status())->toBe(PayoutStatus::PROCESSING);
});

it('cannot start a payout that is already started', function (): void {
    $payout = createTestPayout();
    $payout->start();

    expect(fn () => $payout->start())->toThrow(DomainException::class);
});

it('completes a processing payout and records PayoutCompleted', function (): void {
    $payout = createTestPayout();
    $payout->start();
    $payout->releaseEvents();

    $externalId = ExternalPayoutId::fromString('ext-payout-123');
    $payout->complete($externalId);

    expect($payout->status())->toBe(PayoutStatus::COMPLETED)
        ->and($payout->externalId()?->equals($externalId))->toBeTrue();

    $events = $payout->releaseEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PayoutCompleted::class);
});

it('cannot complete a payout that was not started', function (): void {
    $payout = createTestPayout();

    expect(fn () => $payout->complete(ExternalPayoutId::fromString('ext-1')))
        ->toThrow(DomainException::class);
});

it('fails a processing payout and records PayoutFailed', function (): void {
    $payout = createTestPayout();
    $payout->start();
    $payout->releaseEvents();

    $payout->fail('Bank rejected the transfer');

    expect($payout->status())->toBe(PayoutStatus::FAILED);

    $events = $payout->releaseEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PayoutFailed::class);
});

it('retries a failed payout', function (): void {
    $payout = createTestPayout();
    $payout->start();
    $payout->fail('Temporary failure');

    $payout->retry();

    expect($payout->status())->toBe(PayoutStatus::PROCESSING)
        ->and($payout->retryCount())->toBe(1);
});

it('cannot retry a payout that is not failed', function (): void {
    $payout = createTestPayout();

    expect(fn () => $payout->retry())->toThrow(DomainException::class);
});

it('cannot retry beyond MAX_RETRY_COUNT', function (): void {
    $payout = createTestPayout();
    $payout->start();

    // Exhaust MAX_RETRY_COUNT - 1 retries (each returns the payout to PROCESSING).
    for ($i = 0; $i < Payout::MAX_RETRY_COUNT - 1; $i++) {
        $payout->fail('Failure '.$i);
        $payout->retry();
    }

    // The final retry reaches the limit.
    $payout->fail('Failure before limit');
    $payout->retry(); // retryCount == MAX_RETRY_COUNT, status PROCESSING

    // Any further failure cannot be retried.
    $payout->fail('Failure over limit');

    expect(fn () => $payout->retry())->toThrow(DomainException::class);
});

it('escalates a failed payout after maximum retries', function (): void {
    $payout = createTestPayout();
    $payout->start();

    // Exhaust MAX_RETRY_COUNT - 1 retries.
    for ($i = 0; $i < Payout::MAX_RETRY_COUNT - 1; $i++) {
        $payout->fail('Failure '.$i);
        $payout->retry();
    }

    // The final retry reaches the limit.
    $payout->fail('Failure before limit');
    $payout->retry(); // retryCount == MAX_RETRY_COUNT

    // Payout fails again — now escalation is allowed.
    $payout->fail('Failure over limit');
    $payout->releaseEvents();

    $payout->escalateForManualReview('Too many retries');

    expect($payout->status())->toBe(PayoutStatus::ON_HOLD);

    $events = $payout->releaseEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PayoutEscalated::class);
});

it('cannot escalate before maximum retries', function (): void {
    $payout = createTestPayout();
    $payout->start();
    $payout->fail('Failure');

    expect(fn () => $payout->escalateForManualReview('Too early'))
        ->toThrow(DomainException::class);
});

it('releases events only once', function (): void {
    $payout = createTestPayout();

    $first = $payout->releaseEvents();
    $second = $payout->releaseEvents();

    expect($first)->toHaveCount(1)
        ->and($second)->toBe([]);
});
