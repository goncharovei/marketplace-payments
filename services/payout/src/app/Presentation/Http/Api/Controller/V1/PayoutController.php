<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Controller\V1;

use App\Application\Command\CreatePayoutCommand;
use App\Application\Dto\PayoutView;
use App\Application\Dto\SellerBalanceView;
use App\Application\Query\GetPayoutByOrderQuery;
use App\Application\Query\GetPayoutQuery;
use App\Application\Query\GetSellerBalanceQuery;
use App\Presentation\Http\Api\Request\V1\CreatePayoutRequest;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

/**
 * HTTP API v1 for Payout Service.
 *
 * Versioning strategy: URL-based (/api/v1/...).
 */
final class PayoutController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Post(
        path: '/api/v1/payouts',
        operationId: 'createPayout',
        description: 'Manually triggers a payout. In production this is invoked by the payout.create command from Order Service.',
        summary: 'Create a payout (dev/test only)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CreatePayoutRequest'),
        ),
        tags: ['Payouts'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Payout created',
                content: new OA\JsonContent(ref: '#/components/schemas/CreatePayoutResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function store(CreatePayoutRequest $request): JsonResponse
    {
        $payoutId = $this->commandBus->send(new CreatePayoutCommand(
            orderId: $request->validated('orderId'),
            sellerId: $request->validated('sellerId'),
            amount: $request->validated('amount'),
            currency: $request->validated('currency'),
        ));

        /** @var PayoutView $view */
        $view = $this->queryBus->send(new GetPayoutQuery($payoutId->toString()));

        return new JsonResponse(
            [
                'payoutId' => $view->id,
                'status' => $view->status,
            ],
            JsonResponse::HTTP_CREATED,
        );
    }

    #[OA\Get(
        path: '/api/v1/payouts/{id}',
        operationId: 'getPayout',
        summary: 'Get a payout by ID',
        tags: ['Payouts'],
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
                description: 'Payout found',
                content: new OA\JsonContent(ref: '#/components/schemas/PayoutView'),
            ),
            new OA\Response(
                response: 404,
                description: 'Payout not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function show(string $id): JsonResponse
    {
        /** @var PayoutView $view */
        $view = $this->queryBus->send(new GetPayoutQuery($id));

        return new JsonResponse($this->serializePayout($view));
    }

    #[OA\Get(
        path: '/api/v1/payouts/by-order/{orderId}',
        operationId: 'getPayoutByOrder',
        summary: 'Get a payout by its parent Order ID',
        tags: ['Payouts'],
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
                description: 'Payout found',
                content: new OA\JsonContent(ref: '#/components/schemas/PayoutView'),
            ),
            new OA\Response(
                response: 404,
                description: 'Payout for the order not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function showByOrder(string $orderId): JsonResponse
    {
        /** @var PayoutView $view */
        $view = $this->queryBus->send(new GetPayoutByOrderQuery($orderId));

        return new JsonResponse($this->serializePayout($view));
    }

    #[OA\Get(
        path: '/api/v1/sellers/{sellerId}/balance',
        operationId: 'getSellerBalance',
        description: 'Returns zeroes if the seller has no payout history yet.',
        summary: 'Get a seller balance',
        tags: ['Sellers'],
        parameters: [
            new OA\Parameter(
                name: 'sellerId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Balance found (may be all zeroes)',
                content: new OA\JsonContent(ref: '#/components/schemas/SellerBalanceView'),
            ),
        ],
    )]
    public function showBalance(string $sellerId): JsonResponse
    {
        /** @var SellerBalanceView $view */
        $view = $this->queryBus->send(new GetSellerBalanceQuery($sellerId));

        return new JsonResponse([
            'sellerId' => $view->sellerId,
            'available' => $view->available,
            'reserved' => $view->reserved,
            'debt' => $view->debt,
            'currency' => $view->currency,
            'updatedAt' => $view->updatedAt,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePayout(PayoutView $view): array
    {
        return [
            'id' => $view->id,
            'orderId' => $view->orderId,
            'sellerId' => $view->sellerId,
            'status' => $view->status,
            'amount' => $view->amount,
            'platformFee' => $view->platformFee,
            'currency' => $view->currency,
            'method' => $view->method,
            'externalId' => $view->externalId,
            'retryCount' => $view->retryCount,
            'createdAt' => $view->createdAt,
            'updatedAt' => $view->updatedAt,
        ];
    }
}
