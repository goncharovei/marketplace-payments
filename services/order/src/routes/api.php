<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes Entry Point
|--------------------------------------------------------------------------
|
| Each API version is a separate file under routes/api/.
| To add v2, create routes/api/v2.php and register it below.
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->group(base_path('routes/api/v1.php'));

// Future:
// Route::prefix('v2')
//     ->name('api.v2.')
//     ->group(base_path('routes/api/v2.php'));
