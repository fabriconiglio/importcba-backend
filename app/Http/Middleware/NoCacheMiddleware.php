<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * MOD-101 (main): Middleware para prevenir cache en endpoints dinámicos
 */
class NoCacheMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Agregar headers anti-cache para endpoints dinámicos
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
        
        // Agregar ETag dinámico basado en timestamp para forzar revalidación
        $etag = md5($request->getUri() . time());
        $response->headers->set('ETag', '"' . $etag . '"');

        return $response;
    }
}
