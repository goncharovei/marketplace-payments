<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Model\Order;
use App\Domain\Repository\OrderRepository;
use App\Domain\ValueObject\OrderId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine ORM implementation of the Order repository.
 *
 * The #[Repository] attribute tells Ecotone that this class is the
 * persistence adapter for the Order aggregate.
 */
final readonly class DoctrineOrderRepository implements OrderRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function save(Order $order): void
    {
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    public function findById(OrderId $id): ?Order
    {
        return $this->entityManager->find(Order::class, $id);
    }

    public function nextIdentity(): OrderId
    {
        return OrderId::generate();
    }
}
