<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'InitiatePaymentRequest',
    required: ['orderId', 'buyerId', 'amount', 'currency', 'method'],
    properties: [
        new OA\Property(property: 'orderId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'buyerId', type: 'string', example: 'buyer-1', maxLength: 64),
        new OA\Property(property: 'amount', description: 'Amount in kopecks', type: 'integer', example: 1000, minimum: 1),
        new OA\Property(property: 'currency', type: 'string', example: 'RUB', maxLength: 3, minLength: 3),
        new OA\Property(
            property: 'method',
            type: 'string',
            example: 'card',
            enum: ['card', 'sbp', 'wallet', 'bank_transfer'],
        ),
    ],
)]
#[OA\Schema(
    schema: 'AuthorizePaymentRequest',
    required: ['externalTransactionId'],
    properties: [
        new OA\Property(property: 'externalTransactionId', type: 'string', example: 'ext-abc123', maxLength: 255),
    ],
)]
final class InitiatePaymentRequestSchema
{
    private function __construct() {}
}
