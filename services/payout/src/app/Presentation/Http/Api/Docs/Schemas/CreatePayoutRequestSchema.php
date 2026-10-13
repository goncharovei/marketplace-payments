<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreatePayoutRequest',
    required: ['orderId', 'sellerId', 'amount', 'currency'],
    properties: [
        new OA\Property(property: 'orderId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'sellerId', type: 'string', example: 'seller-1', maxLength: 64),
        new OA\Property(property: 'amount', description: 'Order total in kopecks; platform fee is deducted automatically', type: 'integer', example: 2500, minimum: 1),
        new OA\Property(property: 'currency', type: 'string', example: 'RUB', maxLength: 3, minLength: 3),
    ],
)]
final class CreatePayoutRequestSchema
{
    private function __construct() {}
}
