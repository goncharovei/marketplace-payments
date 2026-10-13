<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\CreatePayoutCommand;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\CommandBus;
use Psr\Log\LoggerInterface;
use Shared\Contracts\CreatePayoutMessage;

final readonly class CreatePayoutConsumer
{
    public function __construct(
        private CommandBus $commandBus,
        private LoggerInterface $logger,
    ) {}

    #[Distributed]
    #[CommandHandler('payout.create')]
    public function handle(CreatePayoutMessage $message): void
    {
        $this->logger->info('Received payout.create', [
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
