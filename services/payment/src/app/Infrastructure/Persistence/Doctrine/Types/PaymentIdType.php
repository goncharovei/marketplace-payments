<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Types;

use App\Domain\ValueObject\PaymentId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class PaymentIdType extends Type
{
    public const NAME = 'payment_id';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 36]);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof PaymentId ? $value->toString() : null;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?PaymentId
    {
        if ($value === null || $value instanceof PaymentId) {
            return $value;
        }

        return PaymentId::fromString($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
