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
 *
 * By default every call succeeds. Use failNext() for a single-shot
 * failure or failFor() for N consecutive failures (useful to exhaust
 * the retry policy in tests).
 */
final class FakePayoutGateway implements PayoutGateway
{
    private int $remainingFailures = 0;

    private ?string $failReason = null;

    public function failNext(string $reason = 'Bank rejected the transfer'): void
    {
        $this->remainingFailures = 1;
        $this->failReason = $reason;
    }

    public function failFor(int $attempts, string $reason = 'Bank rejected the transfer'): void
    {
        $this->remainingFailures = $attempts;
        $this->failReason = $reason;
    }

    public function send(Money $amount, PayoutMethod $method): ExternalPayoutId
    {
        if ($this->remainingFailures > 0) {
            $this->remainingFailures--;
            $reason = $this->failReason ?? 'Unknown error';

            if ($this->remainingFailures === 0) {
                $this->failReason = null;
            }

            throw new PayoutGatewayException($reason);
        }

        return ExternalPayoutId::fromString('ext-'.bin2hex(random_bytes(8)));
    }
}
