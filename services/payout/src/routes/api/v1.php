<?php

declare(strict_types=1);

use App\Presentation\Http\Api\Controller\V1\PayoutController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| Served under the /api/v1 prefix (configured in routes/api.php).
|
*/

Route::post('/payouts', [PayoutController::class, 'store'])
    ->name('payouts.store');

// Declared before /payouts/{id} — otherwise Laravel would treat
// "by-order" as the {id} path parameter.
Route::get('/payouts/by-order/{orderId}', [PayoutController::class, 'showByOrder'])
    ->name('payouts.showByOrder');

Route::get('/payouts/{id}', [PayoutController::class, 'show'])
    ->name('payouts.show');

Route::get('/sellers/{sellerId}/balance', [PayoutController::class, 'showBalance'])
    ->name('sellers.balance');
