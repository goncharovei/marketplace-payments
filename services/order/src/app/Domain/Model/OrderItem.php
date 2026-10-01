<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductId;
use InvalidArgumentException;

/**
 * Line item inside the Order aggregate.
 *
 * This is an internal entity - it has no global identity outside of the Order.
 * Quantity can change; price is immutable once set.
 */
final class OrderItem
{
    private function __construct(
        private ProductId $productId,
        private int $quantity,
        private Money $price,
    ) {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be positive.');
        }
    }

    public static function create(ProductId $productId, int $quantity, Money $price): self
    {
        return new self($productId, $quantity, $price);
    }

    public function productId(): ProductId
    {
        return $this->productId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function subtotal(): Money
    {
        return $this->price->multiply($this->quantity);
    }

    public function changeQuantity(int $newQuantity): void
    {
        if ($newQuantity <= 0) {
            throw new InvalidArgumentException('Quantity must be positive.');
        }

        $this->quantity = $newQuantity;
    }

    public function equals(self $other): bool
    {
        return $this->productId->equals($other->productId);
    }
}
