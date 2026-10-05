<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Supported payment methods.
 */
enum PaymentMethod: string
{
    case CARD = 'card';
    case SBP = 'sbp';
    case WALLET = 'wallet';
    case BANK_TRANSFER = 'bank_transfer';
}
