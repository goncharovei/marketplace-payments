<?php

declare(strict_types=1);

use App\Presentation\Http\Api\Controller\V1\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| Served under the /api/v1 prefix (configured in routes/api.php).
|
*/

Route::post('/payments', [PaymentController::class, 'store'])
    ->name('payments.store');

// Declared before /payments/{id} — otherwise Laravel would treat
// "by-order" as the {id} path parameter.
Route::get('/payments/by-order/{orderId}', [PaymentController::class, 'showByOrder'])
    ->name('payments.showByOrder');

Route::get('/payments/{id}', [PaymentController::class, 'show'])
    ->name('payments.show');

Route::post('/payments/{id}/authorize', [PaymentController::class, 'authorize'])
    ->name('payments.authorize');

Route::post('/payments/{id}/capture', [PaymentController::class, 'capture'])
    ->name('payments.capture');

Route::post('/payments/{id}/refund', [PaymentController::class, 'refund'])
    ->name('payments.refund');
