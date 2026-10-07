<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\InitiatePaymentCommand;
use App\Application\Dto\OrderCreatedMessage;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\CommandBus;
use Psr\Log\LoggerInterface;

/**
 * Consumes the order.created message from the Distributed Bus (RabbitMQ)
 * and initiates a new payment for the order.
 *
 * This is the primary integration point between Order Service and
 * Payment Service.
 */
final readonly class OrderCreatedConsumer
{
    public function __construct(
        private CommandBus $commandBus,
        private LoggerInterface $logger,
    ) {}

    #[Distributed]
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
            method: 'card', // Default method; could be extended later.
        ));

        $this->logger->info('Payment initiated from order.created', [
            'orderId' => $message->orderId,
            'paymentId' => $paymentId->toString(),
        ]);
    }
}
