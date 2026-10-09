<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Model\Payout;
use App\Domain\Repository\PayoutRepository;
use App\Domain\ValueObject\PayoutId;
use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\StandardRepository;
use InvalidArgumentException;

#[Repository]
final readonly class EcotonePayoutRepository implements StandardRepository
{
    public function __construct(
        private PayoutRepository $payouts,
    ) {}

    public function canHandle(string $aggregateClassName): bool
    {
        return $aggregateClassName === Payout::class;
    }

    public function findBy(string $aggregateClassName, array $identifiers): ?object
    {
        $id = PayoutId::fromString($identifiers['id']);

        return $this->payouts->findById($id);
    }

    public function save(array $identifiers, object $aggregate, array $metadata, ?int $expectedVersion): void
    {
        if (! $aggregate instanceof Payout) {
            throw new InvalidArgumentException('Expected a Payout aggregate.');
        }

        $this->payouts->save($aggregate);
    }
}
