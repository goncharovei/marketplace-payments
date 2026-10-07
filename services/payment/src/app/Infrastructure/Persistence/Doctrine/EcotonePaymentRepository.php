<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Model\Payment;
use App\Domain\Repository\PaymentRepository;
use App\Domain\ValueObject\PaymentId;
use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\StandardRepository;
use InvalidArgumentException;

/**
 * Ecotone adapter for the Payment aggregate.
 * Delegates persistence to the domain PaymentRepository.
 */
#[Repository]
final readonly class EcotonePaymentRepository implements StandardRepository
{
    public function __construct(
        private PaymentRepository $payments,
    ) {}

    public function canHandle(string $aggregateClassName): bool
    {
        return $aggregateClassName === Payment::class;
    }

    public function findBy(string $aggregateClassName, array $identifiers): ?object
    {
        $id = PaymentId::fromString($identifiers['id']);

        return $this->payments->findById($id);
    }

    public function save(array $identifiers, object $aggregate, array $metadata, ?int $expectedVersion): void
    {
        if (! $aggregate instanceof Payment) {
            throw new InvalidArgumentException('Expected a Payment aggregate.');
        }

        $this->payments->save($aggregate);
    }
}
