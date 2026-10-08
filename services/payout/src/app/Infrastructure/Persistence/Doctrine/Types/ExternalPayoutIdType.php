<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Types;

use App\Domain\ValueObject\ExternalPayoutId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class ExternalPayoutIdType extends Type
{
    public const NAME = 'external_payout_id';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 255]);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof ExternalPayoutId ? $value->toString() : null;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?ExternalPayoutId
    {
        if ($value === null || $value instanceof ExternalPayoutId) {
            return $value;
        }

        return ExternalPayoutId::fromString($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
