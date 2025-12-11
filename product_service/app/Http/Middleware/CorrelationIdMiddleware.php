<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CorrelationIdMiddleware
{
    public function handle($request, Closure $next)
    {
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

        $request->attributes->set('correlation_id', $correlationId);
        Log::withContext(['correlation_id'=>$correlationId]);

        $response = $next($request);
        $response->headers->set('X-Correlation-ID',$correlationId);

        return $response;
    }
}

