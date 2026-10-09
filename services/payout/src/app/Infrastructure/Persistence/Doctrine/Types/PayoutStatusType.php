<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Types;

use App\Domain\Model\PayoutStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class PayoutStatusType extends Type
{
    public const NAME = 'payout_status';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 32]);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof PayoutStatus ? $value->value : null;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?PayoutStatus
    {
        if ($value === null || $value instanceof PayoutStatus) {
            return $value;
        }

        return PayoutStatus::from($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
