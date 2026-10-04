<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreateOrderRequest',
    required: ['buyerId', 'sellerId', 'items'],
    properties: [
        new OA\Property(property: 'buyerId', type: 'string', example: 'buyer-1', maxLength: 64),
        new OA\Property(property: 'sellerId', type: 'string', example: 'seller-1', maxLength: 64),
        new OA\Property(
            property: 'items',
            type: 'array',
            minItems: 1,
            items: new OA\Items(ref: '#/components/schemas/CreateOrderItem'),
        ),
    ],
)]
#[OA\Schema(
    schema: 'CreateOrderItem',
    required: ['productId', 'quantity', 'amount', 'currency'],
    properties: [
        new OA\Property(property: 'productId', type: 'string', example: 'product-1', maxLength: 64),
        new OA\Property(property: 'quantity', type: 'integer', example: 2, minimum: 1),
        new OA\Property(property: 'amount', type: 'integer', example: 500, minimum: 1, description: 'Amount in smallest currency unit (kopecks)'),
        new OA\Property(property: 'currency', type: 'string', example: 'RUB', minLength: 3, maxLength: 3),
    ],
)]
final class CreateOrderRequestSchema
{
    private function __construct() {}
}
