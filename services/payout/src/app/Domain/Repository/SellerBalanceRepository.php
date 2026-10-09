<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\SellerBalance;
use App\Domain\ValueObject\SellerId;

interface SellerBalanceRepository
{
    public function save(SellerBalance $balance): void;

    public function findBySellerId(SellerId $sellerId): ?SellerBalance;
}
