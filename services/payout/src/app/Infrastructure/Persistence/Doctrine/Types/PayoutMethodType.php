<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Types;

use App\Domain\Model\PayoutMethod;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class PayoutMethodType extends Type
{
    public const NAME = 'payout_method';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 32]);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof PayoutMethod ? $value->value : null;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?PayoutMethod
    {
        if ($value === null || $value instanceof PayoutMethod) {
            return $value;
        }

        return PayoutMethod::from($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
