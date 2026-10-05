<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Types;

use App\Domain\ValueObject\RefundId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class RefundIdType extends Type
{
    public const NAME = 'refund_id';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 36]);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof RefundId ? $value->toString() : null;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?RefundId
    {
        if ($value === null || $value instanceof RefundId) {
            return $value;
        }

        return RefundId::fromString($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
