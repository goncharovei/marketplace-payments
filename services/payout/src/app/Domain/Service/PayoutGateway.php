<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Exception\PayoutGatewayException;
use App\Domain\Model\PayoutMethod;
use App\Domain\ValueObject\ExternalPayoutId;
use App\Domain\ValueObject\Money;

/**
 * External boundary for sending money to a seller.
 *
 * Implementations live in Infrastructure and emulate a real
 * payout provider (bank, SBP, wallet).
 */
interface PayoutGateway
{
    /**
     * Sends money to the seller using the given method.
     *
     * @throws PayoutGatewayException
     */
    public function send(Money $amount, PayoutMethod $method): ExternalPayoutId;
}
