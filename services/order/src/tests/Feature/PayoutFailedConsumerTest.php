<?php

declare(strict_types=1);

use App\Application\Dto\PayoutFailedSignal;
use App\Application\Handler\PayoutFailedConsumer;
use App\Domain\ValueObject\OrderId;
use Ecotone\Modelling\EventBus;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Contracts\PayoutFailedMessage;

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

it('publishes PayoutFailedSignal for the saga', function (): void {
    $orderId = OrderId::generate()->toString();

    /** @var EventBus&MockInterface $eventBus */
    $eventBus = Mockery::mock(EventBus::class);
    $eventBus
        ->shouldReceive('publish')
        ->once()
        ->withArgs(function (mixed $event) use ($orderId): bool {
            return $event instanceof PayoutFailedSignal
                && $event->orderId === $orderId
                && $event->reason === 'Bank rejected';
        });

    $this->app->instance(EventBus::class, $eventBus);

    app(PayoutFailedConsumer::class)->handle(new PayoutFailedMessage(
        payoutId: 'payout-2',
        orderId: $orderId,
        sellerId: 'seller-1',
        reason: 'Bank rejected',
    ));
});
