<?php

declare(strict_types=1);

use App\Application\Dto\PayoutCompletedMessage;
use App\Application\Handler\PayoutCompletedConsumer;
use Psr\Log\LoggerInterface;

it('logs payout.completed event', function (): void {
    $spy = Mockery::spy(LoggerInterface::class);
    $this->app->instance(LoggerInterface::class, $spy);

    app(PayoutCompletedConsumer::class)->handle(new PayoutCompletedMessage(
        payoutId: 'payout-1',
        orderId: 'order-1',
        sellerId: 'seller-1',
        amount: 900,
        currency: 'RUB',
    ));

    $spy->shouldHaveReceived('info')
        ->withArgs(function (string $message, array $context): bool {
            return $message === 'Payout completed for order'
                && $context['orderId'] === 'order-1'
                && $context['payoutId'] === 'payout-1';
        })
        ->once();
});
