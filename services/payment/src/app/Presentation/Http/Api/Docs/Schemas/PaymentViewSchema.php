<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaymentView',
    required: [
        'id', 'orderId', 'buyerId', 'status', 'amount', 'currency',
        'refundedAmount', 'method', 'refunds', 'createdAt', 'updatedAt',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'orderId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'buyerId', type: 'string', example: 'buyer-1'),
        new OA\Property(
            property: 'status',
            type: 'string',
            example: 'initiated',
            enum: ['initiated', 'authorized', 'captured', 'failed', 'refunded', 'partially_refunded'],
        ),
        new OA\Property(property: 'amount', description: 'Amount in kopecks', type: 'integer', example: 1000),
        new OA\Property(property: 'currency', type: 'string', example: 'RUB'),
        new OA\Property(property: 'refundedAmount', type: 'integer', example: 0),
        new OA\Property(
            property: 'method',
            type: 'string',
            example: 'card',
            enum: ['card', 'sbp', 'wallet', 'bank_transfer'],
        ),
        new OA\Property(property: 'externalId', type: 'string', example: null, nullable: true),
        new OA\Property(
            property: 'refunds',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/RefundView'),
        ),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'RefundView',
    required: ['id', 'amount', 'currency', 'reason', 'createdAt'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'amount', type: 'integer', example: 500),
        new OA\Property(property: 'currency', type: 'string', example: 'RUB'),
        new OA\Property(property: 'reason', type: 'string', example: 'Out of stock'),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
    ],
)]
final class PaymentViewSchema
{
    private function __construct() {}
}
