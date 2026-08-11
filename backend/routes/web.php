<?php

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SPA fallback (same-host deploy)
|--------------------------------------------------------------------------
| Controller (not closure) so `php artisan route:cache` works on Render.
*/
Route::get('/{any?}', SpaController::class)->where('any', '.*');
