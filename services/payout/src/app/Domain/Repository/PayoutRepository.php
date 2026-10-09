<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Payout;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PayoutId;

interface PayoutRepository
{
    public function save(Payout $payout): void;

    public function findById(PayoutId $id): ?Payout;

    public function findByOrderId(OrderId $orderId): ?Payout;

    public function nextIdentity(): PayoutId;
}
