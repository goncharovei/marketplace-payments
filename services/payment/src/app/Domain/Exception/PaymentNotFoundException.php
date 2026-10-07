<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\ValueObject\PaymentId;
use RuntimeException;

final class PaymentNotFoundException extends RuntimeException
{
    public static function withId(PaymentId $id): self
    {
        return new self(sprintf('Payment not found: %s', $id->toString()));
    }
}
