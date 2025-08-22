<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheControlMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo aplicar cache a archivos estáticos
        if ($request->is('storage/*')) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000'); // 1 año
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000));
            
            // Añadir ETag para validación de cache
            if ($request->isMethod('GET')) {
                $etag = md5($request->getPathInfo() . filemtime(public_path($request->getPathInfo())));
                $response->headers->set('ETag', '"' . $etag . '"');
                
                // Si el cliente tiene la misma versión, retornar 304
                if ($request->header('If-None-Match') === '"' . $etag . '"') {
                    return response('', 304);
                }
            }
        }

        return $response;
    }
}
