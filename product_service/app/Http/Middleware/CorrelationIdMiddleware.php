<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CorrelationIdMiddleware
{
    public function handle($request, Closure $next)
    {
        // Ambil atau buat Correlation ID
        $correlationId = $request->header('X-Correlation-ID')
            ?? (string) Str::uuid();

        // Simpan ke request (dipakai controller)
        $request->attributes->set('correlation_id', $correlationId);

        // Logging context (distributed logging)
        Log::withContext([
            'correlation_id' => $correlationId
        ]);

        // Proses request
        $response = $next($request);

        // Tambahkan header ke response
        $response->headers->set('X-Correlation-ID', $correlationId);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');

        return $response;
    }
}
