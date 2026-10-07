<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Payment;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentId;

/**
 * Repository contract for the Payment aggregate.
 *
 * Implementations live in Infrastructure. The Domain and Application
 * layers depend only on this interface.
 */
interface PaymentRepository
{
    public function save(Payment $payment): void;

    public function findById(PaymentId $id): ?Payment;
    public function findByOrderId(OrderId $orderId): ?Payment;

    public function nextIdentity(): PaymentId;
}
