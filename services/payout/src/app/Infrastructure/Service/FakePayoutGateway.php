<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Exception\PayoutGatewayException;
use App\Domain\Model\PayoutMethod;
use App\Domain\Service\PayoutGateway;
use App\Domain\ValueObject\ExternalPayoutId;
use App\Domain\ValueObject\Money;

/**
 * Emulates an external payout provider.
 * Always succeeds unless `failNext()` is called.
 */
final class FakePayoutGateway implements PayoutGateway
{
    private bool $shouldFail = false;

    private ?string $failReason = null;

    public function failNext(string $reason = 'Bank rejected the transfer'): void
    {
        $this->shouldFail = true;
        $this->failReason = $reason;
    }

    public function send(Money $amount, PayoutMethod $method): ExternalPayoutId
    {
        if ($this->shouldFail) {
            $this->shouldFail = false;
            $reason = $this->failReason ?? 'Unknown error';
            $this->failReason = null;

            throw new PayoutGatewayException($reason);
        }

        return ExternalPayoutId::fromString('ext-'.bin2hex(random_bytes(8)));
    }
}
