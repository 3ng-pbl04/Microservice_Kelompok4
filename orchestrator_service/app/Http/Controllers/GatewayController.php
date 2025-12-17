<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GatewayController extends Controller
{
    public function users(Request $request)
    {
        $correlationId = $request->attributes->get('correlation_id');
        $token = $request->bearerToken();

        try {
            $response = Http::withHeaders([
                'Authorization' => $token ? "Bearer $token" : '',
                'X-Correlation-ID' => $correlationId,
            ])->get(config('services.user.url').'/api/users');

            return response()->json([
                'status' => 'success',
                'service' => 'user',
                'data' => $response->json(),
            ]);

        } catch (\Exception $e) {
            Log::error('User Service error', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'User Service Unavailable'
            ], 503);
        }
    }

    public function products(Request $request)
    {
        $correlationId = $request->attributes->get('correlation_id');

        try {
            $response = Http::withHeaders([
                'X-Correlation-ID' => $correlationId,
            ])->get(config('services.product.url').'/api/products');

            return response()->json([
                'status' => 'success',
                'service' => 'product',
                'data' => $response->json(),
            ]);

        } catch (\Exception $e) {
            Log::error('Product Service error', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Product Service Unavailable'
            ], 503);
        }
    }

    public function userWithProducts(Request $request, $userId)
    {
        $correlationId = $request->attributes->get('correlation_id');
        $token = $request->bearerToken();

        Log::info("Gateway: Getting user with products", [
            'correlation_id' => $correlationId,
            'user_id' => $userId
        ]);

        try {
            // 1. Get user data
            $userResponse = Http::withHeaders([
                'Authorization' => $token ? "Bearer $token" : '',
                'X-Correlation-ID' => $correlationId,
            ])->get(config('services.user.url').'/api/users/' . $userId);

            if ($userResponse->failed()) {
                return response()->json([
                    'status' => 'error',
                    'service' => 'gateway',
                    'message' => 'User Service Error: ' . $userResponse->body()
                ], $userResponse->status());
            }

            $userData = $userResponse->json();

            // 2. Get user's products
            try {
                $productsResponse = Http::withHeaders([
                    'X-Correlation-ID' => $correlationId,
                ])->get(config('services.product.url').'/api/products?user_id=' . $userId);

                if ($productsResponse->failed()) {
                    // ✅ PERBAIKAN: Kembalikan 207 ketika product service gagal
                    Log::warning('Product Service failed in combined endpoint', [
                        'status' => $productsResponse->status(),
                        'user_id' => $userId
                    ]);

                    return response()->json([
                        'status' => 'partial_success',
                        'service' => 'gateway_orchestrator',
                        'correlation_id' => $correlationId,
                        'data' => [
                            'user' => $userData,
                            'products' => [],
                            'product_service_status' => 'unavailable'
                        ],
                        'message' => 'Product Service temporarily unavailable'
                    ], 207); // ✅ 207 = Multi-Status
                }

                $productsData = $productsResponse->json();

                // Success with both services
                return response()->json([
                    'status' => 'success',
                    'service' => 'gateway_orchestrator',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'user' => $userData,
                        'products' => $productsData,
                        'product_service_status' => 'available'
                    ]
                ], 200);

            } catch (\Exception $e) {
                // ✅ PERBAIKAN: Kembalikan 207 ketika product service exception
                Log::error('Product Service error in combined endpoint', [
                    'message' => $e->getMessage(),
                    'user_id' => $userId
                ]);

                return response()->json([
                    'status' => 'partial_success',
                    'service' => 'gateway_orchestrator',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'user' => $userData,
                        'products' => [],
                        'product_service_status' => 'unavailable'
                    ],
                    'message' => 'Product Service temporarily unavailable: ' . $e->getMessage()
                ], 207); // ✅ 207 = Multi-Status
            }

        } catch (\Exception $e) {
            // User service failed
            Log::error('User Service error in combined endpoint', [
                'message' => $e->getMessage(),
                'user_id' => $userId
            ]);

            return response()->json([
                'status' => 'error',
                'service' => 'gateway',
                'message' => 'User Service Unavailable: ' . $e->getMessage()
            ], 503);
        }
    }

    // ✅ BONUS: Simple version
    public function userWithProductsSimple(Request $request, $userId)
    {
        $correlationId = $request->attributes->get('correlation_id');

        try {
            $response = Http::withHeaders([
                'X-Correlation-ID' => $correlationId,
            ])->get(config('services.product.url').'/api/products?user_id=' . $userId);

            return response()->json([
                'status' => 'success',
                'service' => 'gateway',
                'data' => $response->json(),
            ]);

        } catch (\Exception $e) {
            Log::error('Product Service error in simple endpoint');

            return response()->json([
                'status' => 'error',
                'message' => 'Product Service Unavailable'
            ], 503);
        }
    }
}