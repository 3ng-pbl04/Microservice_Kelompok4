<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Ambil dari header atau buat baru
        $correlationId = $request->header('X-Correlation-ID')
            ?? (string) Str::uuid();

        // Simpan ke request
        $request->attributes->set('correlation_id', $correlationId);

        // Tambahkan ke log context
        Log::withContext([
            'correlation_id' => $correlationId,
            'service' => 'user-service',
        ]);

        Log::info('User Service menerima request');

        $response = $next($request);

        // Kirim balik ke response
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}
