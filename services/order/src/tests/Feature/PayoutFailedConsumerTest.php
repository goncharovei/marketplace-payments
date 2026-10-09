<?php

declare(strict_types=1);

use App\Application\Dto\PayoutFailedMessage;
use App\Application\Handler\PayoutFailedConsumer;
use Psr\Log\LoggerInterface;

it('logs payout.failed event', function (): void {
    $spy = Mockery::spy(LoggerInterface::class);
    $this->app->instance(LoggerInterface::class, $spy);

    app(PayoutFailedConsumer::class)->handle(new PayoutFailedMessage(
        payoutId: 'payout-1',
        orderId: 'order-1',
        sellerId: 'seller-1',
        reason: 'Bank rejected',
    ));

    $spy->shouldHaveReceived('error')
        ->withArgs(function (string $message, array $context): bool {
            return $message === 'Payout failed for order — manual review required'
                && $context['reason'] === 'Bank rejected';
        })
        ->once();
});
