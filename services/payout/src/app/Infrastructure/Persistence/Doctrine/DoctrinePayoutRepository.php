<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Event\DomainEventPublisher;
use App\Domain\Model\Payout;
use App\Domain\Repository\PayoutRepository;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PayoutId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrinePayoutRepository implements PayoutRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DomainEventPublisher $eventPublisher,
    ) {}

    public function save(Payout $payout): void
    {
        $this->entityManager->persist($payout);
        $this->entityManager->flush();

        $this->eventPublisher->publish(...$payout->releaseEvents());
    }

    public function findById(PayoutId $id): ?Payout
    {
        return $this->entityManager->find(Payout::class, $id);
    }

    public function findByOrderId(OrderId $orderId): ?Payout
    {
        return $this->entityManager
            ->getRepository(Payout::class)
            ->findOneBy(['orderId' => $orderId]);
    }

    public function nextIdentity(): PayoutId
    {
        return PayoutId::generate();
    }
}
