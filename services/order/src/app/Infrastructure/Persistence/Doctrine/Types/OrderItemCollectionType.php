<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Types;

use App\Domain\Model\OrderItem;
use App\Domain\Model\OrderItemCollection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Persists the entire OrderItemCollection as a single JSONB column.
 *
 * This keeps the Order aggregate self-contained in a single row
 * and avoids separate order_items table for now.
 */
final class OrderItemCollectionType extends Type
{
    public const NAME = 'order_item_collection';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getJsonTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (!$value instanceof OrderItemCollection) {
            return null;
        }

        $items = [];
        foreach ($value as $item) {
            $items[] = [
                'productId' => $item->productId()->toString(),
                'quantity' => $item->quantity(),
                'price' => [
                    'amount' => $item->price()->amount(),
                    'currency' => $item->price()->currency(),
                ],
            ];
        }

        return json_encode($items, JSON_THROW_ON_ERROR);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?OrderItemCollection
    {
        if ($value === null || $value instanceof OrderItemCollection) {
            return $value;
        }

        if (is_string($value)) {
            $value = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        }

        if (!is_array($value) || $value === []) {
            return OrderItemCollection::empty();
        }

        $items = array_map(
            fn (array $item): OrderItem => OrderItem::create(
                \App\Domain\ValueObject\ProductId::fromString($item['productId']),
                (int) $item['quantity'],
                \App\Domain\ValueObject\Money::of(
                    (int) $item['price']['amount'],
                    $item['price']['currency'],
                ),
            ),
            $value,
        );

        return OrderItemCollection::fromArray(...$items);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
