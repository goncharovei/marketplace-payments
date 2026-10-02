<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Types;

use App\Domain\Model\OrderItem;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Serializes a single OrderItem into a JSON-compatible array.
 * Used internally by OrderItemCollectionType.
 */
final class OrderItemType extends Type
{
    public const NAME = 'order_item';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if ($platform instanceof PostgreSQLPlatform) {
            return $platform->getJsonbTypeDeclarationSQL($column);
        }

        return $platform->getJsonTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?array
    {
        if (! $value instanceof OrderItem) {
            return null;
        }

        return [
            'productId' => $value->productId()->toString(),
            'quantity' => $value->quantity(),
            'price' => [
                'amount' => $value->price()->amount(),
                'currency' => $value->price()->currency(),
            ],
        ];
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?OrderItem
    {
        if ($value === null || $value instanceof OrderItem) {
            return $value;
        }

        if (is_string($value)) {
            $value = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        }

        return OrderItem::create(
            ProductId::fromString($value['productId']),
            (int) $value['quantity'],
            Money::of(
                (int) $value['price']['amount'],
                $value['price']['currency'],
            ),
        );
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
