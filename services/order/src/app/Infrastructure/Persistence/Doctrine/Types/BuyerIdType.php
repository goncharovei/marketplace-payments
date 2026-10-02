<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Types;

use App\Domain\ValueObject\BuyerId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class BuyerIdType extends Type
{
    public const NAME = 'buyer_id';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 64]);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof BuyerId ? $value->toString() : null;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?BuyerId
    {
        if ($value === null || $value instanceof BuyerId) {
            return $value;
        }

        return BuyerId::fromString($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
