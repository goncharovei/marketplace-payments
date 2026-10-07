<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Event\DomainEventPublisher;
use App\Domain\Model\Payment;
use App\Domain\Repository\PaymentRepository;
use App\Domain\ValueObject\PaymentId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine ORM implementation of the Payment repository.
 * Publishes recorded domain events after successful flush.
 */
final readonly class DoctrinePaymentRepository implements PaymentRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DomainEventPublisher $eventPublisher,
    ) {}

    public function save(Payment $payment): void
    {
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        $this->eventPublisher->publish(...$payment->releaseEvents());
    }

    public function findById(PaymentId $id): ?Payment
    {
        return $this->entityManager->find(Payment::class, $id);
    }

    public function nextIdentity(): PaymentId
    {
        return PaymentId::generate();
    }
}
