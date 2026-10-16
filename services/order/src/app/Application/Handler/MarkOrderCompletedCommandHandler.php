<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\MarkOrderCompletedCommand;
use App\Domain\Model\OrderStatus;
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
            // Permanent condition — ack so the message leaves the queue.
            $this->logger->error('Order not found, MarkOrderCompletedCommand acked', [
                'orderId' => $command->orderId,
            ]);

            return;
        }

        // Idempotency: payout.completed may be delivered more than once.
        if ($order->status() === OrderStatus::COMPLETED) {
            $this->logger->info('Order already completed, MarkOrderCompletedCommand skipped', [
                'orderId' => $command->orderId,
            ]);

            return;
        }

        // Transient: payment.completed has not been processed yet.
        // Retry is legitimate here.
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
