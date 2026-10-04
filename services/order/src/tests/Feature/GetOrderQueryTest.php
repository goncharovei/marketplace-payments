<?php

declare(strict_types=1);

use App\Application\Command\PlaceOrderCommand;
use App\Application\Query\GetOrderQuery;
use App\Domain\Exception\OrderNotFoundException;
use App\Domain\ValueObject\OrderId;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;

it('returns an order view by id', function (): void {
    $orderId = app(CommandBus::class)->send(new PlaceOrderCommand(
        buyerId: 'buyer-1',
        sellerId: 'seller-1',
        items: [
            ['productId' => 'product-1', 'quantity' => 2, 'amount' => 500, 'currency' => 'RUB'],
        ],
    ));

    $view = app(QueryBus::class)->send(new GetOrderQuery($orderId->toString()));

    expect($view->id)->toBe($orderId->toString())
        ->and($view->buyerId)->toBe('buyer-1')
        ->and($view->sellerId)->toBe('seller-1')
        ->and($view->status)->toBe('created')
        ->and($view->totalAmount)->toBe(1000)
        ->and($view->currency)->toBe('RUB')
        ->and($view->items)->toHaveCount(1)
        ->and($view->items[0]['productId'])->toBe('product-1')
        ->and($view->items[0]['quantity'])->toBe(2);
});

it('throws when order not found', function (): void {
    expect(fn () => app(QueryBus::class)->send(
        new GetOrderQuery(OrderId::generate()->toString()),
    ))->toThrow(OrderNotFoundException::class);
});
