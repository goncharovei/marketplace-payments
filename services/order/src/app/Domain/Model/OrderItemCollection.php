<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductId;
use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * Immutable collection of OrderItem entities.
 * Enforces uniqueness of productId within a single order.
 *
 * @implements IteratorAggregate<int, OrderItem>
 */
final readonly class OrderItemCollection implements Countable, IteratorAggregate
{
    /** @var OrderItem[] */
    private array $items;

    private function __construct(array $items)
    {
        $this->items = $items;
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public static function fromArray(OrderItem ...$items): self
    {
        $uniqueItems = [];
        foreach ($items as $item) {
            $productId = $item->productId()->toString();
            if (isset($uniqueItems[$productId])) {
                throw new InvalidArgumentException(
                    sprintf('Duplicate product in order: %s', $productId),
                );
            }
            $uniqueItems[$productId] = $item;
        }

        return new self(array_values($uniqueItems));
    }

    public function add(OrderItem $item): self
    {
        $productId = $item->productId()->toString();

        foreach ($this->items as $existing) {
            if ($existing->productId()->equals($item->productId())) {
                throw new InvalidArgumentException(
                    sprintf('Product %s already exists in the order.', $productId),
                );
            }
        }

        return new self([...$this->items, $item]);
    }

    public function remove(ProductId $productId): self
    {
        $filtered = array_filter(
            $this->items,
            fn (OrderItem $item): bool => ! $item->productId()->equals($productId),
        );

        return new self(array_values($filtered));
    }

    public function total(): Money
    {
        if ($this->items === []) {
            throw new InvalidArgumentException('Cannot calculate total of empty collection.');
        }

        $currency = $this->items[0]->price()->currency();
        $total = Money::zero($currency);

        foreach ($this->items as $item) {
            $total = $total->add($item->subtotal());
        }

        return $total;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /** @return Traversable<int, OrderItem> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
