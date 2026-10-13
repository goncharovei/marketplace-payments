<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RefundPaymentRequest',
    required: ['amount', 'currency', 'reason'],
    properties: [
        new OA\Property(property: 'amount', description: 'Amount in kopecks', type: 'integer', example: 500, minimum: 1),
        new OA\Property(property: 'currency', type: 'string', example: 'RUB', maxLength: 3, minLength: 3),
        new OA\Property(property: 'reason', type: 'string', example: 'Out of stock', maxLength: 500),
    ],
)]
final class RefundPaymentRequestSchema
{
    private function __construct() {}
}
