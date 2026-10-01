<?php

declare(strict_types=1);

use App\Domain\Event\OrderCreated;
use App\Domain\ValueObject\BuyerId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\SellerId;

it('carries all order data', function (): void {
    $orderId = OrderId::generate();
    $buyerId = BuyerId::fromString('buyer-1');
    $sellerId = SellerId::fromString('seller-1');
    $amount = Money::of(1000, 'RUB');

    $event = OrderCreated::now($orderId, $buyerId, $sellerId, $amount);

    expect($event->orderId->equals($orderId))->toBeTrue()
        ->and($event->buyerId->equals($buyerId))->toBeTrue()
        ->and($event->sellerId->equals($sellerId))->toBeTrue()
        ->and($event->totalAmount->equals($amount))->toBeTrue()
        ->and($event->occurredAt())->toBeInstanceOf(DateTimeImmutable::class);
});
