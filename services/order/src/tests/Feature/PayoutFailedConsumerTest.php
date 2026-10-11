<?php

declare(strict_types=1);

use App\Application\Dto\PayoutFailedMessage;
use App\Application\Handler\PayoutFailedConsumer;
use App\Domain\ValueObject\OrderId;
use Psr\Log\LoggerInterface;

it('logs payout.failed and publishes an internal signal', function (): void {
    $spy = Mockery::spy(LoggerInterface::class);
    $this->app->instance(LoggerInterface::class, $spy);

    app(PayoutFailedConsumer::class)->handle(new PayoutFailedMessage(
        payoutId: 'payout-1',
        orderId: OrderId::generate()->toString(),
        sellerId: 'seller-1',
        reason: 'Bank rejected',
    ));

    $spy->shouldHaveReceived('error')
        ->withArgs(function (string $message, array $context): bool {
            return str_contains($message, 'Payout failed')
                && $context['reason'] === 'Bank rejected';
        })
        ->once();
});
