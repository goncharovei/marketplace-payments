<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Identifier assigned by the external payment provider.
 * Format is provider-specific and not validated here.
 */
final readonly class ExternalTransactionId
{
    private function __construct(
        private string $value,
    ) {
        if (trim($value) === '') {
            throw new InvalidArgumentException('ExternalTransactionId cannot be empty.');
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

    public function __toString(): string
    {
        return $this->toString();
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
