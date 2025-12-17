<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
