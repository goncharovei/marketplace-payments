<?php

declare(strict_types=1);

use App\Application\Command\AuthorizePaymentCommand;
use App\Application\Command\CapturePaymentCommand;
use App\Application\Command\InitiatePaymentCommand;
use App\Application\Dto\PaymentCompletedMessage;
use App\Domain\ValueObject\OrderId;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\DistributedBus;
use Mockery\MockInterface;

it('publishes payment.completed after capture', function (): void {
    $orderId = OrderId::generate()->toString();

    $paymentId = app(CommandBus::class)->send(new InitiatePaymentCommand(
        orderId: $orderId,
        buyerId: 'buyer-1',
        amount: 1500,
        currency: 'RUB',
        method: 'card',
    ));

    app(CommandBus::class)->send(new AuthorizePaymentCommand(
        paymentId: $paymentId->toString(),
        externalTransactionId: 'ext-notifier-test',
    ));

    /** @var DistributedBus&MockInterface $distributedBus */
    $distributedBus = Mockery::mock(DistributedBus::class);
    $distributedBus
        ->shouldReceive('convertAndPublishEvent')
        ->once()
        ->withArgs(function (string $routingKey, mixed $event) use ($orderId): bool {
            return $routingKey === 'payment.completed'
                && $event instanceof PaymentCompletedMessage
                && $event->orderId === $orderId
                && $event->amount === 1500
                && $event->currency === 'RUB';
        });

    $this->app->instance(DistributedBus::class, $distributedBus);

    app(CommandBus::class)->send(new CapturePaymentCommand(
        paymentId: $paymentId->toString(),
    ));
});
