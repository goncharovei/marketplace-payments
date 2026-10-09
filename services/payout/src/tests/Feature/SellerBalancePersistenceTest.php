<?php

declare(strict_types=1);

use App\Domain\Model\SellerBalance;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\SellerId;

function makeBalance(string $sellerId = 'seller-balance'): SellerBalance
{
    return SellerBalance::start(
        SellerId::fromString($sellerId),
        'RUB',
    );
}

it('persists and reloads a seller balance', function (): void {
    $balance = makeBalance();
    $balance->earn(Money::of(1000, 'RUB'));
    $balance->reserve(Money::of(400, 'RUB'));

    $this->em->persist($balance);
    $this->em->flush();
    $this->em->clear();

    $reloaded = $this->em->find(
        SellerBalance::class,
        SellerId::fromString('seller-balance'),
    );

    expect($reloaded)->not->toBeNull()
        ->and($reloaded->available()->amount())->toBe(600)
        ->and($reloaded->reserved()->amount())->toBe(400)
        ->and($reloaded->debt()->amount())->toBe(0);
});

it('persists debt', function (): void {
    $balance = makeBalance('seller-debt');
    $balance->increaseDebt(Money::of(500, 'RUB'));

    $this->em->persist($balance);
    $this->em->flush();
    $this->em->clear();

    $reloaded = $this->em->find(
        SellerBalance::class,
        SellerId::fromString('seller-debt'),
    );

    expect($reloaded->debt()->amount())->toBe(500);
});
