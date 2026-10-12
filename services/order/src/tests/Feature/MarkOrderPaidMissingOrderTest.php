<?php

declare(strict_types=1);

use App\Application\Command\MarkOrderPaidCommand;
use App\Domain\ValueObject\OrderId;
use Ecotone\Modelling\CommandBus;
use Psr\Log\LoggerInterface;

it('acks without throwing when order is missing', function (): void {
    $spy = Mockery::spy(LoggerInterface::class);
    $this->app->instance(LoggerInterface::class, $spy);

    $missingOrderId = OrderId::generate()->toString();

    // Must not throw — otherwise Ecotone retries forever.
    app(CommandBus::class)->send(new MarkOrderPaidCommand($missingOrderId));

    $spy->shouldHaveReceived('error')
        ->withArgs(function (string $message, array $context) use ($missingOrderId): bool {
            return $message === 'Order not found, MarkOrderPaidCommand acked'
                && $context['orderId'] === $missingOrderId;
        })
        ->once();
});
