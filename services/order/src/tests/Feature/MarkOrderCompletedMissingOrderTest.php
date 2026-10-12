<?php

declare(strict_types=1);

use App\Application\Command\MarkOrderCompletedCommand;
use App\Domain\ValueObject\OrderId;
use Ecotone\Modelling\CommandBus;
use Psr\Log\LoggerInterface;

it('acks without throwing when order is missing', function (): void {
    $spy = Mockery::spy(LoggerInterface::class);
    $this->app->instance(LoggerInterface::class, $spy);

    $missingOrderId = OrderId::generate()->toString();

    app(CommandBus::class)->send(new MarkOrderCompletedCommand($missingOrderId));

    $spy->shouldHaveReceived('error')
        ->withArgs(function (string $message, array $context) use ($missingOrderId): bool {
            return $message === 'Order not found, MarkOrderCompletedCommand acked'
                && $context['orderId'] === $missingOrderId;
        })
        ->once();
});
