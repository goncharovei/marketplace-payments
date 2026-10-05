<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Supported payment methods.
 */
enum PaymentMethod: string
{
    case Card = 'card';
    case Sbp = 'sbp';
    case Wallet = 'wallet';
    case BankTransfer = 'bank_transfer';
}
