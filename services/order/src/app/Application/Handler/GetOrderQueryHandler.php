<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Dto\OrderView;
use App\Application\Query\GetOrderQuery;
use App\Domain\Exception\OrderNotFoundException;
use App\Domain\Model\Order;
use App\Domain\Model\OrderItem;
use App\Domain\Repository\OrderRepository;
use App\Domain\ValueObject\OrderId;
use Ecotone\Modelling\Attribute\QueryHandler;

final readonly class GetOrderQueryHandler
{
    public function __construct(
        private OrderRepository $orders,
    ) {}

    #[QueryHandler]
    public function handle(GetOrderQuery $query): OrderView
    {
        $orderId = OrderId::fromString($query->orderId);
        $order = $this->orders->findById($orderId);

        if ($order === null) {
            throw OrderNotFoundException::withId($orderId);
        }

        return $this->toView($order);
    }

    private function toView(Order $order): OrderView
    {
        $items = array_map(
            static fn (OrderItem $item): array => [
                'productId' => $item->productId()->toString(),
                'quantity' => $item->quantity(),
                'amount' => $item->price()->amount(),
                'currency' => $item->price()->currency(),
            ],
            iterator_to_array($order->items()),
        );

        return new OrderView(
            id: $order->id()->toString(),
            buyerId: $order->buyerId()->toString(),
            sellerId: $order->sellerId()->toString(),
            status: $order->status()->value,
            totalAmount: $order->totalAmount()->amount(),
            currency: $order->totalAmount()->currency(),
            items: $items,
            createdAt: $order->createdAt()->format(DATE_ATOM),
            updatedAt: $order->updatedAt()->format(DATE_ATOM),
        );
    }
}
