<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\MarkOrderCompletedCommand;
use App\Domain\Exception\OrderNotFoundException;
use App\Domain\Repository\OrderRepository;
use App\Domain\ValueObject\OrderId;
use DomainException;
use Ecotone\Modelling\Attribute\CommandHandler;
use Psr\Log\LoggerInterface;

final readonly class MarkOrderCompletedCommandHandler
{
    public function __construct(
        private OrderRepository $orders,
        private LoggerInterface $logger,
    ) {}

    #[CommandHandler]
    public function handle(MarkOrderCompletedCommand $command): void
    {
        $orderId = OrderId::fromString($command->orderId);
        $order = $this->orders->findById($orderId);

        if ($order === null) {
            // The order may not be committed yet (race with MarkOrderPaid).
            // Throw so Ecotone retries the message.
            $this->logger->warning('Order not found yet — will retry', [
                'orderId' => $orderId->toString(),
            ]);

            throw OrderNotFoundException::withId($orderId);
        }

        // The order may still be in PaymentProcessing if payment.completed
        // has not been processed yet. Throw so the message is retried.
        if (! $order->status()->canBeCompleted()) {
            $this->logger->warning('Order not ready for completion yet — will retry', [
                'orderId' => $orderId->toString(),
                'currentStatus' => $order->status()->value,
            ]);

            throw new DomainException(sprintf(
                'Order %s is not in a completable state yet: %s',
                $orderId->toString(),
                $order->status()->value,
            ));
        }

        $order->markAsCompleted();
        $this->orders->save($order);
    }
}
