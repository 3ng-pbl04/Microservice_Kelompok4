<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GatewayController;

Route::middleware(['api'])->group(function () {
    Route::get('/users', [GatewayController::class, 'users']);
    Route::get('/products', [GatewayController::class, 'products']);
    Route::get('/users/{userId}/with-products', [GatewayController::class, 'userWithProducts']);
    
    // ✅ Health check endpoint
    Route::get('/health', function () {
        return response()->json([
            'status' => 'healthy',
            'service' => 'gateway_orchestrator',
            'timestamp' => now()->toISOString()
        ]);
    });
});