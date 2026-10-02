<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Model\Order;
use App\Domain\Repository\OrderRepository;
use App\Domain\ValueObject\OrderId;
use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\StandardRepository;

/**
 * Ecotone adapter for the Order aggregate.
 *
 * Delegates persistence to the domain OrderRepository.
 */
#[Repository]
final readonly class EcotoneOrderRepository implements StandardRepository
{
    public function __construct(
        private OrderRepository $orders,
    ) {}

    public function canHandle(string $aggregateClassName): bool
    {
        return $aggregateClassName === Order::class;
    }

    public function findBy(string $aggregateClassName, array $identifiers): ?object
    {
        $id = OrderId::fromString($identifiers['id']);

        return $this->orders->findById($id);
    }

    public function save(array $identifiers, object $aggregate, array $metadata, ?int $expectedVersion): void
    {
        if (! $aggregate instanceof Order) {
            throw new \InvalidArgumentException('Expected an Order aggregate.');
        }

        $this->orders->save($aggregate);
    }
}
