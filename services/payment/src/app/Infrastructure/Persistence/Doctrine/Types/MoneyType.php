<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Types;

use App\Domain\ValueObject\Money;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Persists Money as a composite {amount, currency} in a JSONB column.
 */
final class MoneyType extends Type
{
    public const NAME = 'money';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if ($platform instanceof PostgreSQLPlatform) {
            return $platform->getJsonbTypeDeclarationSQL($column);
        }

        return $platform->getJsonTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (! $value instanceof Money) {
            return null;
        }

        return json_encode([
            'amount' => $value->amount(),
            'currency' => $value->currency(),
        ], JSON_THROW_ON_ERROR);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Money
    {
        if ($value === null || $value instanceof Money) {
            return $value;
        }

        if (is_string($value)) {
            $value = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        }

        return Money::of((int) $value['amount'], $value['currency']);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
