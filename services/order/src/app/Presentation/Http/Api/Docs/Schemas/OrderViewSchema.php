<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderView',
    required: ['id', 'buyerId', 'sellerId', 'status', 'totalAmount', 'currency', 'items', 'createdAt', 'updatedAt'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'buyerId', type: 'string', example: 'buyer-1'),
        new OA\Property(property: 'sellerId', type: 'string', example: 'seller-1'),
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['created', 'payment_processing', 'paid', 'cancelled', 'refunded'],
            example: 'created',
        ),
        new OA\Property(property: 'totalAmount', type: 'integer', example: 1000, description: 'Amount in kopecks'),
        new OA\Property(property: 'currency', type: 'string', example: 'RUB'),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/OrderItemView'),
        ),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'OrderItemView',
    required: ['productId', 'quantity', 'amount', 'currency'],
    properties: [
        new OA\Property(property: 'productId', type: 'string', example: 'product-1'),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'amount', type: 'integer', example: 500),
        new OA\Property(property: 'currency', type: 'string', example: 'RUB'),
    ],
)]
final class OrderViewSchema
{
    private function __construct() {}
}
