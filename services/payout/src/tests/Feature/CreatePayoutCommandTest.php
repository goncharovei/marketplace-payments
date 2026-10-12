<?php

declare(strict_types=1);

use App\Application\Command\CreatePayoutCommand;
use App\Domain\Model\Payout;
use App\Domain\Model\PayoutStatus;
use App\Domain\Model\SellerBalance;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PayoutId;
use App\Domain\ValueObject\SellerId;
use App\Infrastructure\Service\FakePayoutGateway;
use Ecotone\Modelling\CommandBus;

it('creates a payout, reserves balance, and completes it', function (): void {
    $orderId = OrderId::generate()->toString();

    $payoutId = app(CommandBus::class)->send(new CreatePayoutCommand(
        orderId: $orderId,
        sellerId: 'seller-1',
        amount: 2500,
        currency: 'RUB',
    ));

    expect($payoutId)->toBeInstanceOf(PayoutId::class);

    $payout = $this->em->find(Payout::class, $payoutId);

    expect($payout)->not->toBeNull()
        ->and($payout->status())->toBe(PayoutStatus::COMPLETED)
        ->and($payout->amount()->amount())->toBe(2250)
        ->and($payout->platformFee()->amount())->toBe(250)
        ->and($payout->retryCount())->toBe(0)
        ->and($payout->externalId())->not->toBeNull();

    $balance = $this->em->find(SellerBalance::class, SellerId::fromString('seller-1'));

    expect($balance)->not->toBeNull()
        ->and($balance->available()->amount())->toBe(0)
        ->and($balance->reserved()->amount())->toBe(0)
        ->and($balance->debt()->amount())->toBe(0);
});

it('is idempotent when called twice for the same order', function (): void {
    $orderId = OrderId::generate()->toString();

    $first = app(CommandBus::class)->send(new CreatePayoutCommand(
        orderId: $orderId,
        sellerId: 'seller-2',
        amount: 1000,
        currency: 'RUB',
    ));

    $second = app(CommandBus::class)->send(new CreatePayoutCommand(
        orderId: $orderId,
        sellerId: 'seller-2',
        amount: 1000,
        currency: 'RUB',
    ));

    expect($first->equals($second))->toBeTrue();
});

it('retries a transient gateway failure and completes on the next attempt', function (): void {
    app(FakePayoutGateway::class)->failNext('Transient network error');

    $orderId = OrderId::generate()->toString();

    $payoutId = app(CommandBus::class)->send(new CreatePayoutCommand(
        orderId: $orderId,
        sellerId: 'seller-retry',
        amount: 1000,
        currency: 'RUB',
    ));

    $payout = $this->em->find(Payout::class, $payoutId);

    expect($payout->status())->toBe(PayoutStatus::COMPLETED)
        ->and($payout->retryCount())->toBe(1);

    $balance = $this->em->find(SellerBalance::class, SellerId::fromString('seller-retry'));

    expect($balance->available()->amount())->toBe(0)
        ->and($balance->reserved()->amount())->toBe(0)
        ->and($balance->debt()->amount())->toBe(0);
});

it('escalates to ON_HOLD after exhausting all retries and increases debt', function (): void {
    // 1 initial attempt + MAX_RETRY_COUNT retries = 6 total failures.
    app(FakePayoutGateway::class)->failFor(
        attempts: Payout::MAX_RETRY_COUNT + 1,
        reason: 'Persistent bank outage',
    );

    $orderId = OrderId::generate()->toString();

    $payoutId = app(CommandBus::class)->send(new CreatePayoutCommand(
        orderId: $orderId,
        sellerId: 'seller-escalated',
        amount: 1000,
        currency: 'RUB',
    ));

    $payout = $this->em->find(Payout::class, $payoutId);

    expect($payout->status())->toBe(PayoutStatus::ON_HOLD)
        ->and($payout->retryCount())->toBe(Payout::MAX_RETRY_COUNT);

    $balance = $this->em->find(SellerBalance::class, SellerId::fromString('seller-escalated'));

    // Reserved money moved to debt — the platform has already captured
    // the buyer's payment, the seller still owes the unfulfilled payout.
    expect($balance->available()->amount())->toBe(0)
        ->and($balance->reserved()->amount())->toBe(0)
        ->and($balance->debt()->amount())->toBe(900);
});
