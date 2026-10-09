<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\CreatePayoutCommand;
use App\Application\Dto\OrderCompletedMessage;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\CommandBus;
use Psr\Log\LoggerInterface;

final readonly class OrderCompletedConsumer
{
    public function __construct(
        private CommandBus $commandBus,
        private LoggerInterface $logger,
    ) {}

    #[Distributed]
    #[CommandHandler('order.completed')]
    public function handle(OrderCompletedMessage $message): void
    {
        $this->logger->info('Received order.completed', [
            'orderId' => $message->orderId,
            'sellerId' => $message->sellerId,
            'amount' => $message->amount,
        ]);

        $this->commandBus->send(new CreatePayoutCommand(
            orderId: $message->orderId,
            sellerId: $message->sellerId,
            amount: $message->amount,
            currency: $message->currency,
        ));
    }
}
