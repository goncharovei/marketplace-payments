<?php

declare(strict_types=1);

use App\Domain\Event\OrderPaid;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\SellerId;

it('carries order id, seller id and amount', function (): void {
    $orderId = OrderId::generate();
    $sellerId = SellerId::fromString('seller-1');
    $amount = Money::of(1000, 'RUB');

    $event = OrderPaid::now($orderId, $sellerId, $amount);

    expect($event->orderId->equals($orderId))->toBeTrue()
        ->and($event->sellerId->equals($sellerId))->toBeTrue()
        ->and($event->amount->equals($amount))->toBeTrue()
        ->and($event->occurredAt())->toBeInstanceOf(DateTimeImmutable::class);
});
