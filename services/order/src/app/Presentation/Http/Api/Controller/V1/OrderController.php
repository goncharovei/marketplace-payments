<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Controller\V1;

use App\Application\Command\CancelOrderCommand;
use App\Application\Command\MarkOrderPaidCommand;
use App\Application\Command\PlaceOrderCommand;
use App\Application\Command\StartPaymentCommand;
use App\Application\Dto\OrderView;
use App\Application\Query\GetOrderQuery;
use App\Presentation\Http\Api\Request\V1\CancelOrderRequest;
use App\Presentation\Http\Api\Request\V1\CreateOrderRequest;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * HTTP API v1 for Order Service.
 *
 * Versioning strategy: URL-based (/api/v1/...).
 * When introducing a breaking change, create a parallel V2 namespace
 * and route /api/v2/... to it. V1 stays available until deprecated.
 */
final class OrderController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $orderId = $this->commandBus->send(new PlaceOrderCommand(
            buyerId: $request->validated('buyerId'),
            sellerId: $request->validated('sellerId'),
            items: $request->validated('items'),
        ));

        return new JsonResponse(
            ['orderId' => $orderId->toString()],
            JsonResponse::HTTP_CREATED,
        );
    }

    public function show(string $id): JsonResponse
    {
        /** @var OrderView $view */
        $view = $this->queryBus->send(new GetOrderQuery($id));

        return new JsonResponse([
            'id' => $view->id,
            'buyerId' => $view->buyerId,
            'sellerId' => $view->sellerId,
            'status' => $view->status,
            'totalAmount' => $view->totalAmount,
            'currency' => $view->currency,
            'items' => $view->items,
            'createdAt' => $view->createdAt,
            'updatedAt' => $view->updatedAt,
        ]);
    }

    public function pay(string $id): JsonResponse
    {
        $this->commandBus->send(new StartPaymentCommand($id));

        return new JsonResponse(['status' => 'payment_started']);
    }

    public function markAsPaid(string $id): JsonResponse
    {
        $this->commandBus->send(new MarkOrderPaidCommand($id));

        return new JsonResponse(['status' => 'paid']);
    }

    public function cancel(string $id, CancelOrderRequest $request): JsonResponse
    {
        $this->commandBus->send(new CancelOrderCommand(
            orderId: $id,
            reason: $request->validated('reason'),
        ));

        return new JsonResponse(['status' => 'cancelled']);
    }
}
