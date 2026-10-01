<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Unique identifier for the Order aggregate.
 * Wraps a UUID v4 to provide type safety and prevent accidental misuse of raw strings.
 */
final readonly class OrderId
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

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private static function isValidUuid(string $value): bool
    {
        return preg_match(self::UUID_V4_PATTERN, $value) === 1;
    }

    /**
     * Generates a UUID v4 from random bytes.
     * No external dependencies - keeps the Domain layer pure.
     */
    private static function generateUuidV4(): string
    {
        $data = random_bytes(16);

        // Set version to 0100 (UUID v4)
        $data[6] = chr(ord($data[6]) & 0x0F | 0x40);

        // Set bits 6-7 to 10 (variant RFC 4122)
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
