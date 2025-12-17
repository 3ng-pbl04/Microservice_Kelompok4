<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
<<<<<<< Updated upstream
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next)
{
    $correlationId = $request->header('X-Correlation-ID')
        ?? (string) \Illuminate\Support\Str::uuid();

    Log::withContext([
        'correlation_id' => $correlationId
    ]);

    $request->attributes->set('correlation_id', $correlationId);

    $response = $next($request);

    $response->headers->set('X-Correlation-ID', $correlationId);

    return $response;
}

}
=======
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

        $response = $next($request);

        // POIN C: Tambahkan correlation ID ke response header
        $response->headers->set('X-Correlation-ID', $correlationId);
        $response->headers->set('X-Service-Name', 'Gateway-Service');
        $response->headers->set('X-Response-Time', now()->toISOString());

        // Log outgoing response
        Log::info('Gateway Response Outgoing', [
            'status_code' => $response->getStatusCode(),
            'correlation_id' => $correlationId,
            'response_time' => microtime(true) - (defined('LARAVEL_START') ? LARAVEL_START : microtime(true))
        ]);

        return $response;
    }
}
>>>>>>> Stashed changes
