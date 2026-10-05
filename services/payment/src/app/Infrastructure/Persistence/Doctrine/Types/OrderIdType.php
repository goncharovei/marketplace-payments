<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Types;

use App\Domain\ValueObject\OrderId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Doctrine type for persisting OrderId as a UUID string.
 */
final class OrderIdType extends Type
{
    public const NAME = 'order_id';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 36]);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof OrderId ? $value->toString() : null;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?OrderId
    {
        if ($value === null || $value instanceof OrderId) {
            return $value;
        }

        return OrderId::fromString($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
