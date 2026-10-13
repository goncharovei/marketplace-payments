<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Controller\V1;

use App\Application\Command\AuthorizePaymentCommand;
use App\Application\Command\CapturePaymentCommand;
use App\Application\Command\InitiatePaymentCommand;
use App\Application\Command\RefundPaymentCommand;
use App\Application\Dto\PaymentView;
use App\Application\Query\GetPaymentByOrderQuery;
use App\Application\Query\GetPaymentQuery;
use App\Presentation\Http\Api\Request\V1\AuthorizePaymentRequest;
use App\Presentation\Http\Api\Request\V1\InitiatePaymentRequest;
use App\Presentation\Http\Api\Request\V1\RefundPaymentRequest;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

/**
 * HTTP API v1 for Payment Service.
 *
 * Versioning strategy: URL-based (/api/v1/...).
 */
final class PaymentController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/api/v1/payments/{id}',
        operationId: 'getPayment',
        summary: 'Get a payment by ID',
        tags: ['Payments'],
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
                description: 'Payment found',
                content: new OA\JsonContent(ref: '#/components/schemas/PaymentView'),
            ),
            new OA\Response(
                response: 404,
                description: 'Payment not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function show(string $id): JsonResponse
    {
        /** @var PaymentView $view */
        $view = $this->queryBus->send(new GetPaymentQuery($id));

        return new JsonResponse($this->serialize($view));
    }

    #[OA\Get(
        path: '/api/v1/payments/by-order/{orderId}',
        operationId: 'getPaymentByOrder',
        summary: 'Get a payment by its parent Order ID',
        tags: ['Payments'],
        parameters: [
            new OA\Parameter(
                name: 'orderId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment found',
                content: new OA\JsonContent(ref: '#/components/schemas/PaymentView'),
            ),
            new OA\Response(
                response: 404,
                description: 'Payment for the order not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function showByOrder(string $orderId): JsonResponse
    {
        /** @var PaymentView $view */
        $view = $this->queryBus->send(new GetPaymentByOrderQuery($orderId));

        return new JsonResponse($this->serialize($view));
    }

    #[OA\Post(
        path: '/api/v1/payments',
        operationId: 'initiatePayment',
        description: 'Manually creates a Payment aggregate. In production this is triggered by order.created.',
        summary: 'Initiate a new payment (dev/test only)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/InitiatePaymentRequest'),
        ),
        tags: ['Payments'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Payment initiated',
                content: new OA\JsonContent(ref: '#/components/schemas/CreatePaymentResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function store(InitiatePaymentRequest $request): JsonResponse
    {
        $paymentId = $this->commandBus->send(new InitiatePaymentCommand(
            orderId: $request->validated('orderId'),
            buyerId: $request->validated('buyerId'),
            amount: $request->validated('amount'),
            currency: $request->validated('currency'),
            method: $request->validated('method'),
        ));

        return new JsonResponse(
            ['paymentId' => $paymentId->toString()],
            JsonResponse::HTTP_CREATED,
        );
    }

    #[OA\Post(
        path: '/api/v1/payments/{id}/authorize',
        operationId: 'authorizePayment',
        description: 'Emulates an external gateway authorization. In production this comes from the provider webhook.',
        summary: 'Authorize a payment (dev/test only)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AuthorizePaymentRequest'),
        ),
        tags: ['Payments'],
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
                description: 'Payment authorized',
                content: new OA\JsonContent(ref: '#/components/schemas/OperationStatusResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'Payment not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Invalid status transition',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function authorize(string $id, AuthorizePaymentRequest $request): JsonResponse
    {
        $this->commandBus->send(new AuthorizePaymentCommand(
            paymentId: $id,
            externalTransactionId: $request->validated('externalTransactionId'),
        ));

        return new JsonResponse(['status' => 'authorized']);
    }

    #[OA\Post(
        path: '/api/v1/payments/{id}/capture',
        operationId: 'capturePayment',
        description: 'Emulates capture. In production this is triggered by payment.capture command from Order Service.',
        summary: 'Capture a payment (dev/test only)',
        tags: ['Payments'],
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
                description: 'Payment captured',
                content: new OA\JsonContent(ref: '#/components/schemas/OperationStatusResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'Payment not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Invalid status transition',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function capture(string $id): JsonResponse
    {
        $this->commandBus->send(new CapturePaymentCommand($id));

        return new JsonResponse(['status' => 'captured']);
    }

    #[OA\Post(
        path: '/api/v1/payments/{id}/refund',
        operationId: 'refundPayment',
        summary: 'Refund a payment (partial or full)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RefundPaymentRequest'),
        ),
        tags: ['Payments'],
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
                description: 'Payment refunded',
                content: new OA\JsonContent(ref: '#/components/schemas/OperationStatusResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'Payment not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Invalid status transition or refund amount',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function refund(string $id, RefundPaymentRequest $request): JsonResponse
    {
        $this->commandBus->send(new RefundPaymentCommand(
            paymentId: $id,
            amount: $request->validated('amount'),
            currency: $request->validated('currency'),
            reason: $request->validated('reason'),
        ));

        return new JsonResponse(['status' => 'refunded']);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(PaymentView $view): array
    {
        return [
            'id' => $view->id,
            'orderId' => $view->orderId,
            'buyerId' => $view->buyerId,
            'status' => $view->status,
            'amount' => $view->amount,
            'currency' => $view->currency,
            'refundedAmount' => $view->refundedAmount,
            'method' => $view->method,
            'externalId' => $view->externalId,
            'refunds' => $view->refunds,
            'createdAt' => $view->createdAt,
            'updatedAt' => $view->updatedAt,
        ];
    }
}
