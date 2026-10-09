<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Model\SellerBalance;
use App\Domain\Repository\SellerBalanceRepository;
use App\Domain\ValueObject\SellerId;
use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\StandardRepository;
use InvalidArgumentException;

#[Repository]
final readonly class EcotoneSellerBalanceRepository implements StandardRepository
{
    public function __construct(
        private SellerBalanceRepository $balances,
    ) {}

    public function canHandle(string $aggregateClassName): bool
    {
        return $aggregateClassName === SellerBalance::class;
    }

    public function findBy(string $aggregateClassName, array $identifiers): ?object
    {
        $id = SellerId::fromString($identifiers['id']);

        return $this->balances->findBySellerId($id);
    }

    public function save(array $identifiers, object $aggregate, array $metadata, ?int $expectedVersion): void
    {
        if (! $aggregate instanceof SellerBalance) {
            throw new InvalidArgumentException('Expected a SellerBalance aggregate.');
        }

        $this->balances->save($aggregate);
    }
}
