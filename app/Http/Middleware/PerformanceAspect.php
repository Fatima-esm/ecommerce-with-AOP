<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceAspect
{
    
    public function handle(Request $request, Closure $next): Response
    {
        $traceId = 'trace-' . str()->uuid();
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        
        // Before Advice
        Log::channel('performance')->info('=== Request Started ===', [
            'trace_id' => $traceId,
            'aspect'   => 'PerformanceAspect',
            'method'   => $request->method(),
            'url'      => $request->fullUrl(),
            'user_id'  => auth()->id(),
            'ip'       => $request->ip(),
        ]);

        $request->headers->set('X-Trace-Id', $traceId);
        $response = $next($request);

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        $memoryUsed = round((memory_get_usage(true) - $startMemory) / (1024 * 1024), 2);

        // After Advice
        Log::channel('performance')->info('=== Request Completed ===', [
            'trace_id'          => $traceId,
            'aspect'            => 'PerformanceAspect',
            'status'            => $response->status(),
            'execution_time_ms' => $executionTime,
            'memory_mb'         => $memoryUsed,
        ]);

        if ($executionTime > 1000) {
            Log::channel('performance')->warning('Slow Request Alert', [
                'trace_id' => $traceId,
                'time_ms'  => $executionTime,
                'url'      => $request->url()
            ]);
        }

        return $response;
    }
}
