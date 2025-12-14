<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::prefix('v1')->group(function () {

    // Product CRUD
    Route::apiResource('products', ProductController::class);

    // Health check
    Route::get('health', function () {
        return response()->json([
            'status' => 'UP',
            'service' => 'product_service',
            'time' => now()
        ]);
    });

});
