<?php

declare(strict_types=1);

use App\Application\Command\CreatePayoutCommand;
use App\Application\Dto\PayoutCompletedMessage;
use App\Domain\ValueObject\OrderId;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\DistributedBus;
use Mockery\MockInterface;

it('publishes payout.completed when a payout is completed', function (): void {
    $orderId = OrderId::generate()->toString();

    /** @var DistributedBus&MockInterface $distributedBus */
    $distributedBus = Mockery::mock(DistributedBus::class);
    $distributedBus
        ->shouldReceive('convertAndPublishEvent')
        ->once()
        ->withArgs(function (string $routingKey, mixed $event) use ($orderId): bool {
            return $routingKey === 'payout.completed'
                && $event instanceof PayoutCompletedMessage
                && $event->orderId === $orderId
                && $event->amount === 2250;
        });

    $this->app->instance(DistributedBus::class, $distributedBus);

    app(CommandBus::class)->send(new CreatePayoutCommand(
        orderId: $orderId,
        sellerId: 'seller-notifier',
        amount: 2500,
        currency: 'RUB',
    ));
});
