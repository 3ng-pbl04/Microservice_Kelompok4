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

            return \App\Helpers\ApiResponse::success('user', $response->json());

        } catch (\Exception $e) {
            Log::error('Product Service error');

            return \App\Helpers\ApiResponse::error('User Service Unavailable', 503);
        }
    }

    private function callService(Request $request, string $serviceName, string $url)
{
    $correlationId = $request->attributes->get('correlation_id');
    $token = $request->bearerToken();

    try {
        $res = Http::withHeaders([
            'Authorization' => $token ? "Bearer $token" : '',
            'X-Correlation-ID' => $correlationId,
        ])->get($url);

        if ($res->failed()) {
            Log::error("Gateway: $serviceName failed", ['status' => $res->status()]);
            return \App\Helpers\ApiResponse::error("$serviceName failed", 503, $res->json());
        }

        return \App\Helpers\ApiResponse::success($serviceName, $res->json());

    } catch (\Exception $e) {
        Log::error("Gateway: $serviceName unreachable", ['error' => $e->getMessage()]);
        return \App\Helpers\ApiResponse::error("$serviceName Unavailable", 503);
    }
}

}
