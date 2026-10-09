<?php

declare(strict_types=1);

use App\Domain\Model\Payout;
use App\Domain\Model\PayoutMethod;
use App\Domain\Model\PayoutStatus;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PayoutId;
use App\Domain\ValueObject\PlatformFee;
use App\Domain\ValueObject\SellerId;

function makePayout(): Payout
{
    return Payout::create(
        PayoutId::generate(),
        SellerId::fromString('seller-persist'),
        OrderId::generate(),
        Money::of(2500, 'RUB'),
        PlatformFee::tenPercent(),
        PayoutMethod::BANK_TRANSFER,
    );
}

it('persists and reloads a payout with all value objects intact', function (): void {
    $payout = makePayout();
    $payoutId = $payout->id();
    $expectedAmount = $payout->amount()->amount();
    $expectedFee = $payout->platformFee()->amount();

    $this->em->persist($payout);
    $this->em->flush();
    $this->em->clear();

    $reloaded = $this->em->find(Payout::class, $payoutId);

    expect($reloaded)->not->toBeNull()
        ->and($reloaded->id()->equals($payoutId))->toBeTrue()
        ->and($reloaded->status())->toBe(PayoutStatus::PENDING)
        ->and($reloaded->amount()->amount())->toBe($expectedAmount)
        ->and($reloaded->amount()->currency())->toBe('RUB')
        ->and($reloaded->platformFee()->amount())->toBe($expectedFee)
        ->and($reloaded->method())->toBe(PayoutMethod::BANK_TRANSFER)
        ->and($reloaded->retryCount())->toBe(0)
        ->and($reloaded->externalId())->toBeNull();
});

it('persists status transitions', function (): void {
    $payout = makePayout();
    $payoutId = $payout->id();

    $payout->start();

    $this->em->persist($payout);
    $this->em->flush();
    $this->em->clear();

    $reloaded = $this->em->find(Payout::class, $payoutId);

    expect($reloaded->status())->toBe(PayoutStatus::PROCESSING);
});

it('persists retry count', function (): void {
    $payout = makePayout();
    $payoutId = $payout->id();

    $payout->start();
    $payout->fail('Temporary failure');
    $payout->retry();

    $this->em->persist($payout);
    $this->em->flush();
    $this->em->clear();

    $reloaded = $this->em->find(Payout::class, $payoutId);

    expect($reloaded->retryCount())->toBe(1)
        ->and($reloaded->status())->toBe(PayoutStatus::PROCESSING);
});
