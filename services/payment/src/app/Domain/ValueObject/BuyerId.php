<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Unique identifier of the buyer (customer).
 */
final readonly class BuyerId
{
    private function __construct(
        private string $value,
    ) {
        if ($value === '') {
            throw new InvalidArgumentException('BuyerId cannot be empty.');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Required by Doctrine for building entity identifiers.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
