<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Docs;

use OpenApi\Attributes as OA;

/**
 * Root OpenAPI document.
 *
 * This class is never instantiated - it only carries
 * #[OA\...] attributes that describe the whole API surface.
 */
#[OA\Info(
    version: '1.0.0',
    title: 'Marketplace Payments — Order Service API',
    description: 'REST API for creating and managing marketplace orders. Built with Laravel 11, Ecotone CQRS, and Doctrine ORM.'
)]
#[OA\Server(
    url: 'http://localhost:8001',
    description: 'Local development server',
)]
#[OA\SecurityScheme(
    securityScheme: 'BearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Enter the token only — the "Bearer " prefix is added automatically.',
)]
#[OA\Tag(
    name: 'Orders',
    description: 'Order lifecycle operations',
)]
final class OpenApi
{
    private function __construct()
    {
        // Never instantiated.
    }
}
