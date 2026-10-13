<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PayoutId;
use RuntimeException;

final class PayoutNotFoundException extends RuntimeException
{
    public static function withPayoutId(PayoutId $id): self
    {
        return new self(sprintf('Payout not found: %s', $id->toString()));
    }

    public static function withOrderId(OrderId $orderId): self
    {
        return new self(sprintf('Payout for order "%s" was not found.', $orderId->toString()));
    }
}
