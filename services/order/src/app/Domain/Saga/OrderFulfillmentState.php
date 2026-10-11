<?php

declare(strict_types=1);

namespace App\Domain\Saga;

enum OrderFulfillmentState: string
{
    case STARTED = 'started';
    case AWAITING_PAYMENT = 'awaiting_payment';
    case PAID = 'paid';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case PAYOUT_FAILED = 'payout_failed';
}
