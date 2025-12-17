<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CorrelationIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // ✅ POIN C: Mengirim dan menerima Correlation ID
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();
        
        // Simpan di request untuk digunakan di controller
        $request->attributes->set('correlation_id', $correlationId);
        
        // Set context untuk logging terdistribusi
        Log::shareContext([
            'correlation_id' => $correlationId,
            'service' => 'gateway',
            'request_id' => Str::uuid(),
            'timestamp' => now()->toISOString()
        ]);
        
        // Log incoming request
        Log::info('Gateway Request Incoming', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all()
        ]);

        // Catat waktu mulai
        $startTime = microtime(true);

        $response = $next($request);

        // ✅ POIN C: Tambahkan correlation ID ke response header
        $response->headers->set('X-Correlation-ID', $correlationId);
        $response->headers->set('X-Service-Name', 'Gateway-Service');
        $response->headers->set('X-Response-Time', now()->toISOString());

        // Hitung response time (alternatif tanpa LARAVEL_START)
        $responseTime = microtime(true) - $startTime;

        // Log outgoing response
        Log::info('Gateway Response Outgoing', [
            'status_code' => $response->getStatusCode(),
            'correlation_id' => $correlationId,
            'response_time_ms' => round($responseTime * 1000, 2) // dalam milidetik
        ]);

        return $response;
    }
}