<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Types;

use App\Domain\ValueObject\ExternalTransactionId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class ExternalTransactionIdType extends Type
{
    public const NAME = 'external_transaction_id';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 255]);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof ExternalTransactionId ? $value->toString() : null;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?ExternalTransactionId
    {
        if ($value === null || $value instanceof ExternalTransactionId) {
            return $value;
        }

        return ExternalTransactionId::fromString($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
