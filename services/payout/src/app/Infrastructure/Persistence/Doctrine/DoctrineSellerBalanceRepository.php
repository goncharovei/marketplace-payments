<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Model\SellerBalance;
use App\Domain\Repository\SellerBalanceRepository;
use App\Domain\ValueObject\SellerId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineSellerBalanceRepository implements SellerBalanceRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function save(SellerBalance $balance): void
    {
        $this->entityManager->persist($balance);
        $this->entityManager->flush();
    }

    public function findBySellerId(SellerId $sellerId): ?SellerBalance
    {
        return $this->entityManager->find(SellerBalance::class, $sellerId);
    }
}
