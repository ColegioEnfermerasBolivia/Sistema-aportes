<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = config('services.public_api.key');
        $providedKey = $request->header('X-API-Key');

        if (
            ! \is_string($configuredKey)
            || $configuredKey === ''
            || ! \is_string($providedKey)
            || \strlen($providedKey) > 128
            || ! \hash_equals($configuredKey, $providedKey)
        ) {
            return response()->json([
                'message' => 'No autorizado.',
            ], Response::HTTP_UNAUTHORIZED, [
                'Cache-Control' => 'no-store',
            ]);
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
