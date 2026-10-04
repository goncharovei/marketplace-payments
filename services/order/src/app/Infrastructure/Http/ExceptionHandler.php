<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Domain\Exception\OrderNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;

final class ExceptionHandler
{
    public function __invoke(Exceptions $exceptions): void
    {
        $exceptions->render(function (OrderNotFoundException $e, $request): ?JsonResponse {
            if ($request->is('api/*') || $request->expectsJson()) {
                return new JsonResponse(
                    ['error' => $e->getMessage()],
                    JsonResponse::HTTP_NOT_FOUND,
                );
            }

            return null;
        });

        $exceptions->render(function (\DomainException $e, $request): ?JsonResponse {
            if ($request->is('api/*') || $request->expectsJson()) {
                return new JsonResponse(
                    ['error' => $e->getMessage()],
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            return null;
        });
    }
}
