<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SPA fallback (same-host deploy)
|--------------------------------------------------------------------------
| When Vue is built into public/index.html, non-API routes serve the SPA.
| Useful with `php artisan serve` (no .htaccess) and as a safety net.
*/
Route::get('/{any?}', function () {
    $spa = public_path('index.html');

    if (file_exists($spa)) {
        return response()->file($spa);
    }

    return view('welcome');
})->where('any', '.*');
