<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\MarkOrderPaidCommand;
use App\Application\Dto\PaymentCompletedMessage;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\CommandBus;
use Psr\Log\LoggerInterface;

final readonly class PaymentCompletedConsumer
{
    public function __construct(
        private CommandBus $commandBus,
        private LoggerInterface $logger,
    ) {}

    #[Distributed]
    #[EventHandler('payment.completed')]
    public function handle(PaymentCompletedMessage $message): void
    {
        $this->logger->info('Received payment.completed', [
            'orderId' => $message->orderId,
            'paymentId' => $message->paymentId,
        ]);

        $this->commandBus->send(new MarkOrderPaidCommand(
            orderId: $message->orderId,
        ));
    }
}
