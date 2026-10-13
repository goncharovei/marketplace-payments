<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Docs;

use OpenApi\Attributes as OA;

/**
 * Root OpenAPI document for Payout Service.
 */
#[OA\Info(
    version: '1.0.0',
    description: 'REST API for managing marketplace payouts and seller balances. Built with Laravel 11, Ecotone CQRS, and Doctrine ORM.',
    title: 'Marketplace Payments — Payout Service API'
)]
#[OA\Server(
    url: 'http://localhost:8003',
    description: 'Local development server',
)]
#[OA\SecurityScheme(
    securityScheme: 'BearerAuth',
    type: 'http',
    description: 'Enter the token only — the "Bearer " prefix is added automatically.',
    bearerFormat: 'JWT',
    scheme: 'bearer',
)]
#[OA\Tag(
    name: 'Payouts',
    description: 'Payout lifecycle operations',
)]
#[OA\Tag(
    name: 'Sellers',
    description: 'Seller balance operations',
)]
final class OpenApi
{
    private function __construct()
    {
        // Never instantiated.
    }
}
