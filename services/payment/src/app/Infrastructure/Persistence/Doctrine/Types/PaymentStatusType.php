<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Types;

use App\Domain\Model\PaymentStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class PaymentStatusType extends Type
{
    public const NAME = 'payment_status';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 32]);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof PaymentStatus ? $value->value : null;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?PaymentStatus
    {
        if ($value === null || $value instanceof PaymentStatus) {
            return $value;
        }

        return PaymentStatus::from($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
