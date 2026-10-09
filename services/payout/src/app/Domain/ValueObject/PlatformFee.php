<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Platform commission expressed in basis points.
 *
 * 10000 basis points = 100.00%
 * 1000  basis points = 10.00%
 */
final readonly class PlatformFee
{
    private const MAX_BASIS_POINTS = 10000;

    private function __construct(
        private int $basisPoints,
    ) {
        if ($basisPoints < 0 || $basisPoints > self::MAX_BASIS_POINTS) {
            throw new InvalidArgumentException(sprintf(
                'PlatformFee must be between 0 and %d basis points.',
                self::MAX_BASIS_POINTS,
            ));
        }
    }

    public static function fromBasisPoints(int $basisPoints): self
    {
        return new self($basisPoints);
    }

    public static function fromPercent(float $percent): self
    {
        return new self((int) round($percent * 100));
    }

    public static function tenPercent(): self
    {
        return new self(1000);
    }

    public function basisPoints(): int
    {
        return $this->basisPoints;
    }

    public function calculate(Money $amount): Money
    {
        $feeAmount = (int) round($amount->amount() * $this->basisPoints / self::MAX_BASIS_POINTS);

        return Money::of($feeAmount, $amount->currency());
    }

    public function equals(self $other): bool
    {
        return $this->basisPoints === $other->basisPoints;
    }
}
