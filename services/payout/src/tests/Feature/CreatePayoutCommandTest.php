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
        ->and($payout->externalId())->not->toBeNull();

    $balance = $this->em->find(SellerBalance::class, SellerId::fromString('seller-1'));

    expect($balance)->not->toBeNull()
        ->and($balance->available()->amount())->toBe(0)
        ->and($balance->reserved()->amount())->toBe(0);
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

it('fails the payout when the gateway rejects the transfer', function (): void {
    $gateway = app(FakePayoutGateway::class);
    $gateway->failNext('Insufficient seller data');

    $orderId = OrderId::generate()->toString();

    $payoutId = app(CommandBus::class)->send(new CreatePayoutCommand(
        orderId: $orderId,
        sellerId: 'seller-3',
        amount: 1000,
        currency: 'RUB',
    ));

    $payout = $this->em->find(Payout::class, $payoutId);

    expect($payout->status())->toBe(PayoutStatus::FAILED);

    $balance = $this->em->find(SellerBalance::class, SellerId::fromString('seller-3'));

    // Net amount (1000 - 10% fee) was earned, reserved and then released back.
    expect($balance->available()->amount())->toBe(900)
        ->and($balance->reserved()->amount())->toBe(0);
});
