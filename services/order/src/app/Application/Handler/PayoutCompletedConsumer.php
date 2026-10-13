<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\MarkOrderCompletedCommand;
use App\Application\Dto\PayoutCompletedSignal;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;
use Psr\Log\LoggerInterface;
use Shared\Contracts\PayoutCompletedMessage;

final readonly class PayoutCompletedConsumer
{
    public function __construct(
        private CommandBus $commandBus,
        private EventBus $eventBus,
        private LoggerInterface $logger,
    ) {}

    #[Distributed]
    #[EventHandler('payout.completed')]
    public function handle(PayoutCompletedMessage $message): void
    {
        $this->logger->info('Payout completed for order', [
            'orderId' => $message->orderId,
            'payoutId' => $message->payoutId,
            'sellerId' => $message->sellerId,
            'amount' => $message->amount,
        ]);

        $this->commandBus->send(new MarkOrderCompletedCommand(
            orderId: $message->orderId,
        ));

        $this->eventBus->publish(PayoutCompletedSignal::now($message->orderId));
    }
}
