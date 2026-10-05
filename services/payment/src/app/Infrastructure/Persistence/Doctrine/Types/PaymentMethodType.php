<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Types;

use App\Domain\Model\PaymentMethod;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class PaymentMethodType extends Type
{
    public const NAME = 'payment_method';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 32]);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof PaymentMethod ? $value->value : null;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?PaymentMethod
    {
        if ($value === null || $value instanceof PaymentMethod) {
            return $value;
        }

        return PaymentMethod::from($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
