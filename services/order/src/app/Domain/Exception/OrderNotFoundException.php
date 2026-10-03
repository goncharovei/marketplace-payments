<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\ValueObject\OrderId;
use RuntimeException;

final class OrderNotFoundException extends RuntimeException
{
    public static function withId(OrderId $id): self
    {
        return new self(sprintf('Order not found: %s', $id->toString()));
    }
}
