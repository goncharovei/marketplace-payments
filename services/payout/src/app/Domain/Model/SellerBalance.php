<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\SellerId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use DomainException;

/**
 * Tracks the money owed to a seller.
 *
 *   available  — money earned and immediately payable
 *   reserved   — money locked while a Payout is in flight
 *   debt       — money the seller owes the platform (compensation)
 *
 * Money flows:
 *   earn(amount)        — order completed: available += amount
 *   reserve(amount)     — payout started:  available -= amount, reserved += amount
 *   release(amount)     — payout failed:   reserved -= amount, available += amount
 *   deduct(amount)      — payout completed: reserved -= amount
 *   increaseDebt(amount)— compensation:    debt += amount
 *   payDebt(amount)     — debt -= amount
 */
#[ORM\Entity]
#[ORM\Table(name: 'seller_balances')]
final class SellerBalance
{
    #[ORM\Id]
    #[ORM\Column(type: 'seller_id')]
    private SellerId $sellerId;

    #[ORM\Column(type: 'money')]
    private Money $available;

    #[ORM\Column(type: 'money')]
    private Money $reserved;

    #[ORM\Column(type: 'money')]
    private Money $debt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        SellerId $sellerId,
        Money $available,
        Money $reserved,
        Money $debt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ) {
        $this->sellerId = $sellerId;
        $this->available = $available;
        $this->reserved = $reserved;
        $this->debt = $debt;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function start(SellerId $sellerId, string $currency): self
    {
        $now = new DateTimeImmutable;

        return new self(
            $sellerId,
            Money::zero($currency),
            Money::zero($currency),
            Money::zero($currency),
            $now,
            $now,
        );
    }

    public function earn(Money $amount): void
    {
        $this->ensurePositive($amount, 'earn');
        $this->available = $this->available->add($amount);
        $this->touch();
    }

    public function reserve(Money $amount): void
    {
        $this->ensurePositive($amount, 'reserve');

        if ($amount->isGreaterThan($this->available)) {
            throw new DomainException('Cannot reserve more than available.');
        }

        $this->available = $this->available->subtract($amount);
        $this->reserved = $this->reserved->add($amount);
        $this->touch();
    }

    public function release(Money $amount): void
    {
        $this->ensurePositive($amount, 'release');

        if ($amount->isGreaterThan($this->reserved)) {
            throw new DomainException('Cannot release more than reserved.');
        }

        $this->reserved = $this->reserved->subtract($amount);
        $this->available = $this->available->add($amount);
        $this->touch();
    }

    public function deduct(Money $amount): void
    {
        $this->ensurePositive($amount, 'deduct');

        if ($amount->isGreaterThan($this->reserved)) {
            throw new DomainException('Cannot deduct more than reserved.');
        }

        $this->reserved = $this->reserved->subtract($amount);
        $this->touch();
    }

    public function increaseDebt(Money $amount): void
    {
        $this->ensurePositive($amount, 'increaseDebt');
        $this->debt = $this->debt->add($amount);
        $this->touch();
    }

    public function payDebt(Money $amount): void
    {
        $this->ensurePositive($amount, 'payDebt');

        if ($amount->isGreaterThan($this->debt)) {
            throw new DomainException('Cannot pay more than the outstanding debt.');
        }

        $this->debt = $this->debt->subtract($amount);
        $this->touch();
    }

    public function sellerId(): SellerId
    {
        return $this->sellerId;
    }

    public function available(): Money
    {
        return $this->available;
    }

    public function reserved(): Money
    {
        return $this->reserved;
    }

    public function debt(): Money
    {
        return $this->debt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function ensurePositive(Money $amount, string $operation): void
    {
        if ($amount->amount() <= 0) {
            throw new DomainException(sprintf(
                'Cannot %s a non-positive amount.',
                $operation,
            ));
        }
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable;
    }
}
