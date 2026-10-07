<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentId;
use RuntimeException;

final class PaymentNotFoundException extends RuntimeException
{
    public static function withPaymentId(PaymentId $id): self
    {
        return new self(sprintf('Payment not found: %s', $id->toString()));
    }

    public static function withOrderId(OrderId $orderId): self
    {
        return new self(sprintf('Payment for order "%s" was not found.', $orderId->toString()));
    }
}
