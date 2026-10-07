<?php

declare(strict_types=1);

use App\Domain\Event\PaymentCaptured;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentId;

it('carries payment id, order id and amount', function (): void {
    $paymentId = PaymentId::generate();
    $orderId = OrderId::generate();
    $amount = Money::of(1000, 'RUB');

    $event = PaymentCaptured::now($paymentId, $orderId, $amount);

    expect($event->paymentId->equals($paymentId))->toBeTrue()
        ->and($event->orderId->equals($orderId))->toBeTrue()
        ->and($event->amount->equals($amount))->toBeTrue()
        ->and($event->occurredAt())->toBeInstanceOf(DateTimeImmutable::class);
});
