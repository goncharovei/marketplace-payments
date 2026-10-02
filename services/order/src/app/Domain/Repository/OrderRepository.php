<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Order;
use App\Domain\ValueObject\OrderId;

/**
 * Repository contract for the Order aggregate.
 *
 * Implementations live in the Infrastructure layer. The Domain and
 * Application layers depend only on this interface.
 */
interface OrderRepository
{
    /**
     * Persists a new or modified Order.
     */
    public function save(Order $order): void;

    /**
     * Finds an Order by its identifier, or null if not found.
     */
    public function findById(OrderId $id): ?Order;

    /**
     * Generates a new identity for a to-be-created Order.
     */
    public function nextIdentity(): OrderId;
}
