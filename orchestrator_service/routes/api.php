<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GatewayController;

// GANTI ['correlation'] dengan [CorrelationIdMiddleware::class]
Route::middleware([CorrelationIdMiddleware::class])->group(function () {
    
    Route::get('/users', [GatewayController::class, 'users']);
    Route::get('/products', [GatewayController::class, 'products']);
    Route::get('/users/{userId}/with-products', [GatewayController::class, 'userWithProducts']);
    
    Route::get('/health', function () {
        return response()->json([
            'status' => 'healthy',
            'service' => 'gateway',
            'timestamp' => now()->toISOString()
        ]);
    });
});