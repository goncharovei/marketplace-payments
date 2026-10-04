<?php

declare(strict_types=1);

use App\Presentation\Http\Api\Controller\V1\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| Served under the /api/v1 prefix (configured in routes/api.php).
|
*/

Route::post('/orders', [OrderController::class, 'store'])
    ->name('orders.store');

Route::get('/orders/{id}', [OrderController::class, 'show'])
    ->name('orders.show');

Route::post('/orders/{id}/pay', [OrderController::class, 'pay'])
    ->name('orders.pay');

Route::post('/orders/{id}/paid', [OrderController::class, 'markAsPaid'])
    ->name('orders.markAsPaid');

Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])
    ->name('orders.cancel');
