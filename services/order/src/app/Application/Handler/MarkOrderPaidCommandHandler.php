<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\MarkOrderPaidCommand;
use App\Domain\Model\OrderStatus;
use App\Domain\Repository\OrderRepository;
use App\Domain\ValueObject\OrderId;
use Ecotone\Modelling\Attribute\CommandHandler;
use Psr\Log\LoggerInterface;

final readonly class MarkOrderPaidCommandHandler
{
    public function __construct(
        private OrderRepository $orders,
        private LoggerInterface $logger,
    ) {}

    #[CommandHandler]
    public function handle(MarkOrderPaidCommand $command): void
    {
        $orderId = OrderId::fromString($command->orderId);
        $order = $this->orders->findById($orderId);

        if ($order === null) {
            // Missing order is permanent — retrying never makes it appear.
            // Ack instead of throw so the message leaves the queue.
            $this->logger->error('Order not found, MarkOrderPaidCommand acked', [
                'orderId' => $command->orderId,
            ]);

            return;
        }

        // Idempotency: payment.completed may be delivered more than once.
        // markAsPaid() would throw DomainException on non-Processing status.
        if ($order->status() !== OrderStatus::PAYMENT_PROCESSING) {
            $this->logger->info('Order not in payment_processing, MarkOrderPaidCommand skipped', [
                'orderId' => $command->orderId,
                'status' => $order->status()->value,
            ]);

            return;
        }

        $order->markAsPaid();
        $this->orders->save($order);
    }
}
