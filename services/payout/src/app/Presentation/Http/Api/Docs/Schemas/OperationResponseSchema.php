<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreatePayoutResponse',
    required: ['payoutId', 'status'],
    properties: [
        new OA\Property(property: 'payoutId', type: 'string', format: 'uuid'),
        new OA\Property(
            property: 'status',
            type: 'string',
            example: 'completed',
            enum: ['pending', 'processing', 'completed', 'failed', 'on_hold'],
        ),
    ],
)]
final class OperationResponseSchema
{
    private function __construct() {}
}
