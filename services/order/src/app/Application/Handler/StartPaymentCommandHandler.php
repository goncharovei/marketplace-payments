<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\StartPaymentCommand;
use App\Domain\Exception\OrderNotFoundException;
use App\Domain\Repository\OrderRepository;
use App\Domain\ValueObject\OrderId;
use Ecotone\Modelling\Attribute\CommandHandler;

final readonly class StartPaymentCommandHandler
{
    public function __construct(
        private OrderRepository $orders,
    ) {}

    #[CommandHandler]
    public function handle(StartPaymentCommand $command): void
    {
        $orderId = OrderId::fromString($command->orderId);
        $order = $this->orders->findById($orderId);

        if ($order === null) {
            throw OrderNotFoundException::withId($orderId);
        }

        $order->startPayment();
        $this->orders->save($order);
    }
}
