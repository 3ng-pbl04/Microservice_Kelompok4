<?php

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
                'Authorization' => "Bearer $token",
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
            Log::error('Product Service error');

            return response()->json([
                'status' => 'error',
                'message' => 'Product Service Unavailable'
            ], 503);
        }
    }
}
