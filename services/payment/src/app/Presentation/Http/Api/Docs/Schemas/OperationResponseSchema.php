<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreatePaymentResponse',
    required: ['paymentId'],
    properties: [
        new OA\Property(property: 'paymentId', type: 'string', format: 'uuid'),
    ],
)]
#[OA\Schema(
    schema: 'OperationStatusResponse',
    required: ['status'],
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'captured'),
    ],
)]
final class OperationResponseSchema
{
    private function __construct() {}
}
