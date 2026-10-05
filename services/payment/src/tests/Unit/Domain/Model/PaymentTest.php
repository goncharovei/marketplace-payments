<?php

declare(strict_types=1);

use App\Domain\Event\PaymentAuthorized;
use App\Domain\Event\PaymentCaptured;
use App\Domain\Event\PaymentFailed;
use App\Domain\Event\PaymentInitiated;
use App\Domain\Event\PaymentRefunded;
use App\Domain\Model\Payment;
use App\Domain\Model\PaymentMethod;
use App\Domain\Model\PaymentStatus;
use App\Domain\ValueObject\BuyerId;
use App\Domain\ValueObject\ExternalTransactionId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentId;

function createTestPayment(?Money $amount = null): Payment
{
    return Payment::initiate(
        PaymentId::generate(),
        OrderId::generate(),
        BuyerId::fromString('buyer-1'),
        $amount ?? Money::of(1000, 'RUB'),
        PaymentMethod::CARD,
    );
}

it('initiates a payment and records PaymentInitiated event', function (): void {
    $payment = createTestPayment();

    expect($payment->status())->toBe(PaymentStatus::INITIATED)
        ->and($payment->amount()->amount())->toBe(1000)
        ->and($payment->refundedAmount()->amount())->toBe(0)
        ->and($payment->refunds())->toBe([])
        ->and($payment->externalId())->toBeNull();

    $events = $payment->releaseEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PaymentInitiated::class);
});

it('rejects initiating a payment with non-positive amount', function (): void {
    expect(fn () => createTestPayment(Money::of(0, 'RUB')))
        ->toThrow(DomainException::class);
});

it('authorizes a payment from Initiated state', function (): void {
    $payment = createTestPayment();
    $payment->releaseEvents();

    $externalId = ExternalTransactionId::fromString('ext-123');
    $payment->authorize($externalId);

    expect($payment->status())->toBe(PaymentStatus::AUTHORIZED);

    $actualExternalId = $payment->externalId();
    expect($actualExternalId)->not->toBeNull()
        ->and($actualExternalId?->equals($externalId))->toBeTrue();

    $events = $payment->releaseEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PaymentAuthorized::class);
});

it('cannot authorize an already authorized payment', function (): void {
    $payment = createTestPayment();
    $payment->authorize(ExternalTransactionId::fromString('ext-1'));

    expect(fn () => $payment->authorize(ExternalTransactionId::fromString('ext-2')))
        ->toThrow(DomainException::class);
});

it('captures an authorized payment', function (): void {
    $payment = createTestPayment();
    $payment->authorize(ExternalTransactionId::fromString('ext-1'));
    $payment->releaseEvents();

    $payment->capture();

    expect($payment->status())->toBe(PaymentStatus::CAPTURED);

    $events = $payment->releaseEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PaymentCaptured::class);
});

it('cannot capture a payment that was not authorized', function (): void {
    $payment = createTestPayment();

    expect(fn () => $payment->capture())
        ->toThrow(DomainException::class);
});

it('fails a payment from non-final state', function (): void {
    $payment = createTestPayment();
    $payment->releaseEvents();

    $payment->fail('Insufficient funds');

    expect($payment->status())->toBe(PaymentStatus::FAILED);

    $events = $payment->releaseEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PaymentFailed::class);

    /** @var PaymentFailed $failedEvent */
    $failedEvent = $events[0];
    expect($failedEvent->reason)->toBe('Insufficient funds');
});

it('cannot fail an already failed payment', function (): void {
    $payment = createTestPayment();
    $payment->fail('reason 1');

    expect(fn () => $payment->fail('reason 2'))
        ->toThrow(DomainException::class);
});

it('refunds a captured payment fully', function (): void {
    $payment = createTestPayment(Money::of(1000, 'RUB'));
    $payment->authorize(ExternalTransactionId::fromString('ext-1'));
    $payment->capture();
    $payment->releaseEvents();

    $refund = $payment->refund(Money::of(1000, 'RUB'), 'Customer request');

    expect($payment->status())->toBe(PaymentStatus::REFUNDED)
        ->and($payment->refundedAmount()->amount())->toBe(1000)
        ->and($payment->refunds())->toHaveCount(1)
        ->and($refund->amount()->amount())->toBe(1000)
        ->and($refund->reason())->toBe('Customer request');

    $events = $payment->releaseEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PaymentRefunded::class);
});

it('partially refunds a captured payment', function (): void {
    $payment = createTestPayment(Money::of(1000, 'RUB'));
    $payment->authorize(ExternalTransactionId::fromString('ext-1'));
    $payment->capture();

    $payment->refund(Money::of(300, 'RUB'), 'Partial refund');

    expect($payment->status())->toBe(PaymentStatus::PARTIALLY_REFUNDED)
        ->and($payment->refundedAmount()->amount())->toBe(300);
});

it('rejects refunding more than the captured amount', function (): void {
    $payment = createTestPayment(Money::of(1000, 'RUB'));
    $payment->authorize(ExternalTransactionId::fromString('ext-1'));
    $payment->capture();

    expect(fn () => $payment->refund(Money::of(1500, 'RUB'), 'Too much'))
        ->toThrow(DomainException::class);
});

it('rejects refunding with different currency', function (): void {
    $payment = createTestPayment(Money::of(1000, 'RUB'));
    $payment->authorize(ExternalTransactionId::fromString('ext-1'));
    $payment->capture();

    expect(fn () => $payment->refund(Money::of(100, 'USD'), 'Wrong currency'))
        ->toThrow(DomainException::class);
});

it('cannot refund a payment that was not captured', function (): void {
    $payment = createTestPayment();

    expect(fn () => $payment->refund(Money::of(100, 'RUB'), 'Too early'))
        ->toThrow(DomainException::class);
});

it('runs the full happy path: initiated → authorized → captured → refunded', function (): void {
    $payment = createTestPayment(Money::of(1000, 'RUB'));
    $payment->releaseEvents();

    $payment->authorize(ExternalTransactionId::fromString('ext-1'));
    $payment->capture();
    $payment->refund(Money::of(1000, 'RUB'), 'Full refund');

    expect($payment->status())->toBe(PaymentStatus::REFUNDED);

    $events = $payment->releaseEvents();
    expect($events)->toHaveCount(3)
        ->and($events[0])->toBeInstanceOf(PaymentAuthorized::class)
        ->and($events[1])->toBeInstanceOf(PaymentCaptured::class)
        ->and($events[2])->toBeInstanceOf(PaymentRefunded::class);
});

it('releases events only once', function (): void {
    $payment = createTestPayment();

    $first = $payment->releaseEvents();
    $second = $payment->releaseEvents();

    expect($first)->toHaveCount(1)
        ->and($second)->toBe([]);
});
