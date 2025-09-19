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

        // MOD-102 (main): Cache diferenciado para archivos estáticos
        if ($request->is('storage/*') || $request->is('*/storage/*')) {
            // Para productos subidos dinámicamente, cache moderado
            $response->headers->set('Cache-Control', 'public, max-age=3600'); // 1 hora
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));
            
            // Añadir ETag para validación de cache
            if ($request->isMethod('GET')) {
                $filePath = public_path($request->getPathInfo());
                if (file_exists($filePath)) {
                    $etag = md5($request->getPathInfo() . filemtime($filePath));
                    $response->headers->set('ETag', '"' . $etag . '"');
                    
                    // Si el cliente tiene la misma versión, retornar 304
                    if ($request->header('If-None-Match') === '"' . $etag . '"') {
                        return response('', 304);
                    }
                }
            }
        }
        
        // Para imágenes por defecto del frontend, cache muy corto
        if ($request->is('image/*') || $request->is('*/image/*')) {
            $response->headers->set('Cache-Control', 'public, max-age=300'); // 5 minutos
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 300));
        }

        return $response;
    }
}
