<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Supported methods for paying out to a seller.
 */
enum PayoutMethod: string
{
    case BANK_TRANSFER = 'bank_transfer';
    case SBP = 'sbp';
    case WALLET = 'wallet';
}
