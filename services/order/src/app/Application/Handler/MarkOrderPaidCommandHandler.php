<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\MarkOrderPaidCommand;
use App\Domain\Exception\OrderNotFoundException;
use App\Domain\Repository\OrderRepository;
use App\Domain\ValueObject\OrderId;
use Ecotone\Modelling\Attribute\CommandHandler;

final readonly class MarkOrderPaidCommandHandler
{
    public function __construct(
        private OrderRepository $orders,
    ) {}

    #[CommandHandler]
    public function handle(MarkOrderPaidCommand $command): void
    {
        $orderId = OrderId::fromString($command->orderId);
        $order = $this->orders->findById($orderId);

        if ($order === null) {
            throw OrderNotFoundException::withId($orderId);
        }

        $order->markAsPaid();

        // For the demo, delivery is instantaneous:
        // the order is marked as completed right after payment.
        $order->markAsCompleted();

        $this->orders->save($order);
    }
}
