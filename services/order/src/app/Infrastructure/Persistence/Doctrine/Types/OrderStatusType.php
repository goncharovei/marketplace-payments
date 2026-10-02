<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Types;

use App\Domain\Model\OrderStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class OrderStatusType extends Type
{
    public const NAME = 'order_status';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 32]);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof OrderStatus ? $value->value : null;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?OrderStatus
    {
        if ($value === null || $value instanceof OrderStatus) {
            return $value;
        }

        return OrderStatus::from($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
