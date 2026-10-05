<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Unique identifier for the Payment aggregate.
 */
final readonly class PaymentId
{
    private const UUID_V4_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    private function __construct(
        private string $value,
    ) {
        if (! self::isValidUuid($value)) {
            throw new InvalidArgumentException(
                sprintf('Invalid UUID: %s', $value),
            );
        }
    }

    public static function generate(): self
    {
        return new self(self::generateUuidV4());
    }

    public static function fromString(string $value): self
    {
        return new self(strtolower($value));
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

    private static function isValidUuid(string $value): bool
    {
        return preg_match(self::UUID_V4_PATTERN, $value) === 1;
    }

    private static function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0F | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
