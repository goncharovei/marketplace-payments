<?php

declare(strict_types=1);

namespace App\Domain\Saga;

/**
 * Lifecycle states of the OrderFulfillmentSaga.
 */
enum OrderFulfillmentState: string
{
    case STARTED = 'started';
    case AWAITING_PAYMENT = 'awaiting_payment';
    case PAYMENT_COMPLETED = 'payment_completed';
    case AWAITING_PAYOUT = 'awaiting_payout';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
}
