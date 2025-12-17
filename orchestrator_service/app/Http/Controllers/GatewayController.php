<?php
<<<<<<< Updated upstream
=======

namespace App\Http\Controllers;
>>>>>>> Stashed changes

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GatewayController extends Controller
{
    // ✅ POIN C: Helper untuk call service dengan error handling konsisten
    private function callService(string $serviceName, string $url, Request $request)
    {
        $correlationId = $request->attributes->get('correlation_id');
        $token = $request->bearerToken();
        
        Log::info("Gateway: Calling {$serviceName} Service", [
            'correlation_id' => $correlationId,
            'url' => $url
        ]);

        try {
<<<<<<< Updated upstream
            $response = Http::withHeaders([
                'Authorization' => "Bearer $token",
=======
            $headers = [
>>>>>>> Stashed changes
                'X-Correlation-ID' => $correlationId,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            // ✅ POIN C: Teruskan Authorization token jika ada
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            $response = Http::withHeaders($headers)
                ->timeout(5)
                ->get($url);

            if ($response->successful()) {
                Log::info("Gateway: {$serviceName} Service Response Success", [
                    'correlation_id' => $correlationId,
                    'status' => $response->status()
                ]);
                
                return [
                    'success' => true,
                    'status' => $response->status(),
                    'data' => $response->json(),
                    'correlation_id' => $correlationId,
                ];
            } else {
                Log::error("Gateway: {$serviceName} Service Failed", [
                    'correlation_id' => $correlationId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'status' => $response->status(),
                    'error' => "{$serviceName} Service Error: " . $response->body(),
                    'correlation_id' => $correlationId,
                ];
            }

        } catch (\Exception $e) {
            Log::error("Gateway: {$serviceName} Service Exception", [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // ✅ POIN C: Error handling konsisten untuk kegagalan service lain
            return [
                'success' => false,
                'status' => 503,
                'error' => "{$serviceName} Service Unavailable: " . $e->getMessage(),
                'correlation_id' => $correlationId,
                'fallback' => $this->getFallbackData($serviceName)
            ];
        }
    }

    // ✅ POIN C: Method untuk GET semua users (call ke User Service)
    public function users(Request $request)
    {
        // ✅ PERBAIKAN: Port 8000 dan /api/v1/users
        $userServiceUrl = config('services.user.url', 'http://localhost:8000');
        $result = $this->callService('User', "{$userServiceUrl}/api/v1/users", $request);
        
        return $this->formatResponse($result);
    }

    // ✅ POIN C: Method untuk GET semua products (call ke Product Service)
    public function products(Request $request)
    {
        // ✅ PERBAIKAN: Port 8001 dan /api/v1/products
        $productServiceUrl = config('services.product.url', 'http://localhost:8001');
        $result = $this->callService('Product', "{$productServiceUrl}/api/v1/products", $request);
        
        return $this->formatResponse($result);
    }

    // ✅ POIN C: COMBINED ENDPOINT - Call ke DUA service sekaligus (User + Product)
    public function userWithProducts(Request $request, $userId)
    {
        Log::info("Gateway: Getting user with products", [
            'correlation_id' => $request->attributes->get('correlation_id'),
            'user_id' => $userId
        ]);

        // ✅ PERBAIKAN: Port dan URL yang benar
        $userServiceUrl = config('services.user.url', 'http://localhost:8000');
        $productServiceUrl = config('services.product.url', 'http://localhost:8001');

        // 1. Call User Service - ✅ /api/v1/users/{id}
        $userResult = $this->callService('User', "{$userServiceUrl}/api/v1/users/{$userId}", $request);
        
        // Jika user service gagal, langsung return error
        if (!$userResult['success']) {
            return $this->formatResponse($userResult);
        }

        // 2. Call Product Service - ✅ /api/v1/products?user_id={id}
        $productResult = $this->callService('Product', "{$productServiceUrl}/api/v1/products?user_id={$userId}", $request);

        // Format response combined
        return response()->json([
            'status' => $productResult['success'] ? 'success' : 'partial_success',
            'service' => 'gateway_orchestrator',
            'correlation_id' => $request->attributes->get('correlation_id'),
            'timestamp' => now()->toISOString(),
            'data' => [
                'user' => $userResult['data'],
                'products' => $productResult['success'] ? $productResult['data'] : [],
                'product_service_status' => $productResult['success'] ? 'available' : 'unavailable'
            ],
            'metadata' => [
                'user_service_status' => $userResult['status'],
                'product_service_status' => $productResult['status']
            ]
        ], $productResult['success'] ? 200 : 207); // 207 = Multi-Status
    }

    // ✅ BONUS: Simple version untuk testing
    public function userWithProductsSimple(Request $request, $userId)
    {
        $correlationId = $request->attributes->get('correlation_id');
        
        // Simple response untuk testing
        return response()->json([
            'status' => 'success',
            'service' => 'gateway',
            'correlation_id' => $correlationId,
            'timestamp' => now()->toISOString(),
            'data' => [
                'user' => ['id' => $userId, 'name' => 'Test User'],
                'products' => [['id' => 1, 'name' => 'Test Product']],
                'product_service_status' => 'available'
            ]
        ]);
    }

<<<<<<< Updated upstream
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
            Log::error('Product Service error');

            return response()->json([
                'status' => 'error',
                'message' => 'Product Service Unavailable'
            ], 503);
        }
    }
}
=======
    // Helper untuk format response konsisten
    private function formatResponse(array $result)
    {
        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'service' => 'gateway',
                'correlation_id' => $result['correlation_id'],
                'timestamp' => now()->toISOString(),
                'data' => $result['data']
            ], $result['status']);
        } else {
            return response()->json([
                'status' => 'error',
                'service' => 'gateway',
                'correlation_id' => $result['correlation_id'],
                'timestamp' => now()->toISOString(),
                'message' => $result['error'],
                'fallback_data' => $result['fallback'] ?? null
            ], $result['status']);
        }
    }

    // Fallback data jika service down
    private function getFallbackData(string $serviceName)
    {
        $fallbacks = [
            'User' => ['message' => 'User service is temporarily unavailable'],
            'Product' => ['message' => 'Product service is temporarily unavailable', 'items' => []]
        ];
        
        return $fallbacks[$serviceName] ?? ['message' => 'Service unavailable'];
    }
}
>>>>>>> Stashed changes
