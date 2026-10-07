<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\InitiatePaymentCommand;
use App\Application\Dto\OrderCreatedMessage;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\CommandBus;
use Psr\Log\LoggerInterface;

final readonly class OrderCreatedConsumer
{
    public function __construct(
        private CommandBus $commandBus,
        private LoggerInterface $logger,
    ) {}

    #[Distributed]
    #[EventHandler('order.created')]
    public function handle(OrderCreatedMessage $message): void
    {
        $this->logger->info('Received order.created', [
            'orderId' => $message->orderId,
            'buyerId' => $message->buyerId,
            'amount' => $message->amount,
            'currency' => $message->currency,
        ]);

        $paymentId = $this->commandBus->send(new InitiatePaymentCommand(
            orderId: $message->orderId,
            buyerId: $message->buyerId,
            amount: $message->amount,
            currency: $message->currency,
            method: 'card',
        ));

        $this->logger->info('Payment initiated from order.created', [
            'orderId' => $message->orderId,
            'paymentId' => $paymentId->toString(),
        ]);
    }
}
