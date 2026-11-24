<?php

use Illuminate\Support\Facades\Route;
use LyraPayment\Http\Controllers\IpnController;
use LyraPayment\Http\Controllers\ReturnController;

$routes = config('lyra-payment.routes');

$csrf = array_values(array_filter([
    'Illuminate\Foundation\Http\Middleware\ValidateCsrfToken',
    'Illuminate\Foundation\Http\Middleware\VerifyCsrfToken',
], 'class_exists'));

Route::prefix($routes['prefix'] ?? 'lyra-payment')
    ->name('lyra-payment.')
    ->group(function () use ($routes, $csrf) {
        Route::post($routes['ipn_path'] ?? 'ipn', IpnController::class)
            ->middleware($routes['ipn_middleware'] ?? [])
            ->name('ipn');

        Route::match(['GET', 'POST'], $routes['return_path'] ?? 'return', ReturnController::class)
            ->middleware($routes['return_middleware'] ?? ['web'])
            ->withoutMiddleware($csrf)
            ->name('return');
    });
