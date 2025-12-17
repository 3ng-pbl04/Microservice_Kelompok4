<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ambil correlation ID dari header atau buat baru
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        
        // Set correlation ID ke context log
        Log::withContext([
            'correlation_id' => $correlationId,
            'request_path' => $request->path(),
            'request_method' => $request->method(),
        ]);
        
        // Log incoming request
        Log::info('Request received', [
            'correlation_id' => $correlationId,
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        // Simpan correlation ID ke request attributes
        $request->attributes->set('correlation_id', $correlationId);
        
        // Process request
        $response = $next($request);
        
        // Tambahkan correlation ID ke response header
        $response->headers->set('X-Correlation-ID', $correlationId);
        
        // Log response
        Log::info('Response sent', [
            'correlation_id' => $correlationId,
            'status_code' => $response->getStatusCode(),
            'path' => $request->path(),
        ]);
        
        return $response;
    }
}