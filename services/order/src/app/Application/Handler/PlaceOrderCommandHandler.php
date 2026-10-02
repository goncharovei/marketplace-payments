<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\PlaceOrderCommand;
use App\Domain\Model\Order;
use App\Domain\Model\OrderItem;
use App\Domain\Model\OrderItemCollection;
use App\Domain\Repository\OrderRepository;
use App\Domain\ValueObject\BuyerId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\ProductId;
use App\Domain\ValueObject\SellerId;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * Handles the PlaceOrderCommand by creating an Order aggregate,
 * persisting it, and returning its identifier.
 */
final readonly class PlaceOrderCommandHandler
{
    public function __construct(
        private OrderRepository $orders,
    ) {}

    #[CommandHandler]
    public function handle(PlaceOrderCommand $command): OrderId
    {
        $items = array_map(
            fn (array $item): OrderItem => OrderItem::create(
                ProductId::fromString($item['productId']),
                $item['quantity'],
                Money::of($item['amount'], $item['currency']),
            ),
            $command->items,
        );

        $order = Order::place(
            $this->orders->nextIdentity(),
            BuyerId::fromString($command->buyerId),
            SellerId::fromString($command->sellerId),
            OrderItemCollection::fromArray(...$items),
        );

        $this->orders->save($order);

        return $order->id();
    }
}
