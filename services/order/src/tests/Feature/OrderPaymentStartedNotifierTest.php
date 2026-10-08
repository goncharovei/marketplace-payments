<?php

declare(strict_types=1);

use App\Application\Command\PlaceOrderCommand;
use App\Application\Command\StartPaymentCommand;
use App\Application\Dto\CapturePaymentMessage;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\DistributedBus;
use Mockery\MockInterface;

it('sends payment.capture command to payment_service on PaymentStarted', function (): void {
    /** @var DistributedBus&MockInterface $distributedBus */
    $distributedBus = Mockery::mock(DistributedBus::class);

    $distributedBus
        ->shouldReceive('convertAndPublishEvent')
        ->zeroOrMoreTimes();

    $distributedBus
        ->shouldReceive('convertAndSendCommand')
        ->once()
        ->withArgs(function (string $targetService, string $routingKey, mixed $command): bool {
            return $targetService === 'payment_service'
                && $routingKey === 'payment.capture'
                && $command instanceof CapturePaymentMessage
                && $command->amount === 2500
                && $command->currency === 'RUB';
        });

    $this->app->instance(DistributedBus::class, $distributedBus);

    $orderId = app(CommandBus::class)->send(new PlaceOrderCommand(
        buyerId: 'buyer-notifier',
        sellerId: 'seller-notifier',
        items: [
            ['productId' => 'product-1', 'quantity' => 1, 'amount' => 2500, 'currency' => 'RUB'],
        ],
    ));

    app(CommandBus::class)->send(new StartPaymentCommand($orderId->toString()));
});
