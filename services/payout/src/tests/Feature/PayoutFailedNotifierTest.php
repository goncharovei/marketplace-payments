<?php

declare(strict_types=1);

use App\Application\Command\CreatePayoutCommand;
use App\Application\Dto\PayoutFailedMessage;
use App\Domain\ValueObject\OrderId;
use App\Infrastructure\Service\FakePayoutGateway;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\DistributedBus;
use Mockery\MockInterface;

it('publishes payout.failed when the gateway rejects the transfer', function (): void {
    app(FakePayoutGateway::class)->failNext('Bank rejected');

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
                && $event->reason === 'Bank rejected';
        });

    $this->app->instance(DistributedBus::class, $distributedBus);

    app(CommandBus::class)->send(new CreatePayoutCommand(
        orderId: $orderId,
        sellerId: 'seller-fail',
        amount: 1000,
        currency: 'RUB',
    ));
});
