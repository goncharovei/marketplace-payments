<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Unique identifier of the product being ordered.
 */
final readonly class ProductId
{
    private function __construct(
        private string $value,
    ) {
        if ($value === '') {
            throw new InvalidArgumentException('ProductId cannot be empty.');
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

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
