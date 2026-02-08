<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSyncKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('sync.api_key');

        if (empty($expected)) {
            return $next($request);
        }

        $provided = $request->header('X-SYNC-KEY')
            ?? $request->header('X-API-KEY')
            ?? $request->query('sync_key');

        if (!is_string($provided) || !hash_equals($expected, $provided)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        return $next($request);
    }
}
