<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GatewayController extends Controller
{
    /**
     * Proxy ke User Service
     */
    public function users(Request $request)
    {
        $correlationId = $request->attributes->get('correlation_id');
        $token = $request->bearerToken();

        try {
            $response = Http::withHeaders([
                'Authorization' => $token ? "Bearer $token" : '',
                'X-Correlation-ID' => $correlationId,
            ])->get(config('services.user.url') . '/api/users');

            return response()->json([
                'status' => 'success',
                'service' => 'user',
                'data' => $response->json(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('User Service error', [
                'correlation_id' => $correlationId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'User Service Unavailable'
            ], 503);
        }
    }

    /**
     * Proxy ke Product Service
     */
    public function products(Request $request)
    {
        $correlationId = $request->attributes->get('correlation_id');

        try {
            $response = Http::withHeaders([
                'X-Correlation-ID' => $correlationId,
            ])->get(config('services.product.url') . '/api/v1/products');

            return response()->json([
                'status' => 'success',
                'service' => 'product',
                'data' => $response->json(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Product Service error', [
                'correlation_id' => $correlationId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Product Service Unavailable'
            ], 503);
        }
    }

    /**
     * ORCHESTRATOR: User + Product
     */
    public function userWithProducts(Request $request, $userId)
    {
        $correlationId = $request->attributes->get('correlation_id');
        $token = $request->bearerToken();

        Log::info('Gateway: Getting user with products', [
            'correlation_id' => $correlationId,
            'user_id' => $userId
        ]);

        try {
            /**
             * 1️⃣ CALL USER SERVICE
             */
            $userResponse = Http::withHeaders([
                'Authorization' => $token ? "Bearer $token" : '',
                'X-Correlation-ID' => $correlationId,
            ])->get(config('services.user.url') . '/api/users/' . $userId);

            if ($userResponse->failed()) {
                return response()->json([
                    'status' => 'error',
                    'service' => 'gateway',
                    'message' => 'User Service Error'
                ], $userResponse->status());
            }

            $userData = $userResponse->json();

            /**
             * 2️⃣ CALL PRODUCT SERVICE
             */
            try {
                $productResponse = Http::withHeaders([
                    'X-Correlation-ID' => $correlationId,
                ])->get(config('services.product.url') . '/api/v1/products?user_id=' . $userId);

                if ($productResponse->failed()) {
                    Log::warning('Product Service failed (partial)', [
                        'correlation_id' => $correlationId,
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
                        ]
                    ], 207); // ✅ WAJIB 207
                }

                /**
                 * ✅ SEMUA SERVICE SUKSES
                 */
                return response()->json([
                    'status' => 'success',
                    'service' => 'gateway_orchestrator',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'user' => $userData,
                        'products' => $productResponse->json(),
                        'product_service_status' => 'available'
                    ]
                ], 200);

            } catch (\Exception $e) {
                Log::error('Product Service exception', [
                    'correlation_id' => $correlationId,
                    'user_id' => $userId,
                    'message' => $e->getMessage()
                ]);

                return response()->json([
                    'status' => 'partial_success',
                    'service' => 'gateway_orchestrator',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'user' => $userData,
                        'products' => [],
                        'product_service_status' => 'unavailable'
                    ]
                ], 207); // ✅ WAJIB 207
            }

        } catch (\Exception $e) {
            Log::error('User Service exception', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'service' => 'gateway',
                'message' => 'User Service Unavailable'
            ], 503);
        }
    }
}
