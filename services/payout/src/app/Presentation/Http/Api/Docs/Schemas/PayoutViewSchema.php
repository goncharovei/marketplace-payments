<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PayoutView',
    required: [
        'id', 'orderId', 'sellerId', 'status', 'amount', 'platformFee',
        'currency', 'method', 'retryCount', 'createdAt', 'updatedAt',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'orderId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'sellerId', type: 'string', example: 'seller-1'),
        new OA\Property(
            property: 'status',
            type: 'string',
            example: 'completed',
            enum: ['pending', 'processing', 'completed', 'failed', 'on_hold'],
        ),
        new OA\Property(property: 'amount', description: 'Payout amount in kopecks (order amount minus platform fee)', type: 'integer', example: 2250),
        new OA\Property(property: 'platformFee', type: 'integer', example: 250),
        new OA\Property(property: 'currency', type: 'string', example: 'RUB'),
        new OA\Property(
            property: 'method',
            type: 'string',
            example: 'bank_transfer',
            enum: ['bank_transfer', 'sbp', 'wallet'],
        ),
        new OA\Property(property: 'externalId', type: 'string', example: 'ext-abc123', nullable: true),
        new OA\Property(property: 'retryCount', type: 'integer', example: 0),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'SellerBalanceView',
    required: ['sellerId', 'available', 'reserved', 'debt', 'currency', 'updatedAt'],
    properties: [
        new OA\Property(property: 'sellerId', type: 'string', example: 'seller-1'),
        new OA\Property(property: 'available', description: 'Amount in kopecks', type: 'integer', example: 0),
        new OA\Property(property: 'reserved', type: 'integer', example: 0),
        new OA\Property(property: 'debt', type: 'integer', example: 0),
        new OA\Property(property: 'currency', type: 'string', example: 'RUB'),
        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
    ],
)]
final class PayoutViewSchema
{
    private function __construct() {}
}
