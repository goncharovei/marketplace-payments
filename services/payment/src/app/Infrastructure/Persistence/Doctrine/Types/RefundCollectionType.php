<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Types;

use App\Domain\Model\Refund;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\RefundId;
use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\Type;

final class RefundCollectionType extends Type
{
    public const NAME = 'refund_collection';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if ($platform instanceof PostgreSQLPlatform) {
            return $platform->getJsonbTypeDeclarationSQL($column);
        }

        return $platform->getJsonTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (! is_array($value)) {
            return null;
        }

        $refunds = [];
        foreach ($value as $refund) {
            if (! $refund instanceof Refund) {
                continue;
            }

            $refunds[] = [
                'id' => $refund->id()->toString(),
                'amount' => $refund->amount()->amount(),
                'currency' => $refund->amount()->currency(),
                'reason' => $refund->reason(),
                'createdAt' => $refund->createdAt()->format(DATE_ATOM),
            ];
        }

        return json_encode($refunds, JSON_THROW_ON_ERROR);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): array
    {
        if ($value === null) {
            return [];
        }

        if (is_string($value)) {
            $value = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        }

        if (! is_array($value)) {
            return [];
        }

        $refunds = [];
        foreach ($value as $item) {
            $refunds[] = Refund::restore(
                RefundId::fromString($item['id']),
                Money::of((int) $item['amount'], $item['currency']),
                $item['reason'],
                new DateTimeImmutable($item['createdAt']),
            );
        }

        return $refunds;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
