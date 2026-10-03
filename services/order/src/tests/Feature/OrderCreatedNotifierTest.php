<?php

declare(strict_types=1);

use App\Application\Command\PlaceOrderCommand;
use App\Application\Dto\OrderCreatedMessage;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\DistributedBus;
use Mockery\MockInterface;

it('publishes order.created to the distributed bus', function (): void {
    /** @var DistributedBus&MockInterface $distributedBus */
    $distributedBus = Mockery::mock(DistributedBus::class);
    $distributedBus
        ->shouldReceive('convertAndPublishEvent')
        ->once()
        ->withArgs(function (string $routingKey, mixed $event): bool {
            return $routingKey === config('ecotone.serviceName')
                && $event instanceof OrderCreatedMessage
                && $event->amount > 0;
        });

    $this->app->instance(DistributedBus::class, $distributedBus);

    app(CommandBus::class)->send(new PlaceOrderCommand(
        buyerId: 'buyer-distributed',
        sellerId: 'seller-distributed',
        items: [
            ['productId' => 'product-1', 'quantity' => 1, 'amount' => 500, 'currency' => 'RUB'],
        ],
    ));
});
