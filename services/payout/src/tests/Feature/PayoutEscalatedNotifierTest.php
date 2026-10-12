<?php

declare(strict_types=1);

use App\Application\Command\CreatePayoutCommand;
use App\Application\Dto\PayoutFailedMessage;
use App\Domain\Model\Payout;
use App\Domain\ValueObject\OrderId;
use App\Infrastructure\Service\FakePayoutGateway;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\DistributedBus;
use Mockery\MockInterface;

it('publishes payout.failed once after retries are exhausted', function (): void {
    app(FakePayoutGateway::class)->failFor(
        attempts: Payout::MAX_RETRY_COUNT + 1,
        reason: 'Persistent bank outage',
    );

    $orderId = OrderId::generate()->toString();

    /** @var DistributedBus&MockInterface $distributedBus */
    $distributedBus = Mockery::mock(DistributedBus::class);
    $distributedBus
        ->shouldReceive('convertAndPublishEvent')
        ->once()
        ->withArgs(function (string $routingKey, mixed $event) use ($orderId): bool {
            return $routingKey === 'payout.failed'
                && $event instanceof PayoutFailedMessage
                && $event->orderId === $orderId
                && $event->sellerId === 'seller-escalated-notifier'
                && $event->reason === 'Persistent bank outage';
        });

    $this->app->instance(DistributedBus::class, $distributedBus);

    app(CommandBus::class)->send(new CreatePayoutCommand(
        orderId: $orderId,
        sellerId: 'seller-escalated-notifier',
        amount: 1000,
        currency: 'RUB',
    ));
});

it('does not publish payout.failed on transient failures that later succeed', function (): void {
    app(FakePayoutGateway::class)->failNext('Transient network error');

    /** @var DistributedBus&MockInterface $distributedBus */
    $distributedBus = Mockery::mock(DistributedBus::class);
    $distributedBus
        ->shouldReceive('convertAndPublishEvent')
        ->withArgs(fn (string $routingKey): bool => $routingKey === 'payout.failed')
        ->never();

    $distributedBus
        ->shouldReceive('convertAndPublishEvent')
        ->withArgs(fn (string $routingKey): bool => $routingKey === 'payout.completed')
        ->once();

    $this->app->instance(DistributedBus::class, $distributedBus);

    app(CommandBus::class)->send(new CreatePayoutCommand(
        orderId: OrderId::generate()->toString(),
        sellerId: 'seller-transient',
        amount: 1000,
        currency: 'RUB',
    ));
});
