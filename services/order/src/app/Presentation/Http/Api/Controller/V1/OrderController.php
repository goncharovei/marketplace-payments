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
use OpenApi\Attributes as OA;

/**
 * HTTP API v1 for Order Service.
 *
 * Versioning strategy: URL-based (/api/v1/...).
 */
final class OrderController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Post(
        path: '/api/v1/orders',
        operationId: 'createOrder',
        summary: 'Create a new order',
        description: 'Places a new order in the system. Emits OrderCreated event.',
        tags: ['Orders'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CreateOrderRequest'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Order created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/CreateOrderResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
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

    #[OA\Get(
        path: '/api/v1/orders/{id}',
        operationId: 'getOrder',
        summary: 'Get an order by ID',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Order found',
                content: new OA\JsonContent(ref: '#/components/schemas/OrderView'),
            ),
            new OA\Response(
                response: 404,
                description: 'Order not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
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

    #[OA\Post(
        path: '/api/v1/orders/{id}/pay',
        operationId: 'startOrderPayment',
        summary: 'Start payment for an order',
        description: 'Moves the order into PaymentProcessing state.',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment started',
                content: new OA\JsonContent(ref: '#/components/schemas/OperationStatusResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'Order not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Invalid status transition',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function pay(string $id): JsonResponse
    {
        $this->commandBus->send(new StartPaymentCommand($id));

        return new JsonResponse(['status' => 'payment_started']);
    }

    #[OA\Post(
        path: '/api/v1/orders/{id}/paid',
        operationId: 'markOrderAsPaid',
        summary: 'Mark order as paid',
        description: 'Moves the order from PaymentProcessing to Paid.',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Order marked as paid',
                content: new OA\JsonContent(ref: '#/components/schemas/OperationStatusResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'Order not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Invalid status transition',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function markAsPaid(string $id): JsonResponse
    {
        $this->commandBus->send(new MarkOrderPaidCommand($id));

        return new JsonResponse(['status' => 'paid']);
    }

    #[OA\Post(
        path: '/api/v1/orders/{id}/cancel',
        operationId: 'cancelOrder',
        summary: 'Cancel an order',
        description: 'Cancels an order. If the order was paid, it is refunded instead.',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reason'],
                properties: [
                    new OA\Property(property: 'reason', type: 'string', example: 'Out of stock', maxLength: 500),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Order cancelled or refunded',
                content: new OA\JsonContent(ref: '#/components/schemas/OperationStatusResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'Order not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Invalid status transition',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function cancel(string $id, CancelOrderRequest $request): JsonResponse
    {
        $this->commandBus->send(new CancelOrderCommand(
            orderId: $id,
            reason: $request->validated('reason'),
        ));

        return new JsonResponse(['status' => 'cancelled']);
    }
}
