<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

/**
 * MOD-102 (main): Controlador para gestión de cache
 */
class CacheController extends BaseApiController
{
    /**
     * Limpiar cache general
     */
    public function clearCache(): JsonResponse
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
            return response()->json([
                'success' => true,
                'message' => 'Cache limpiado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invalidar cache de imágenes específicas
     */
    public function invalidateImageCache(Request $request): JsonResponse
    {
        try {
            $imageUrls = $request->input('images', []);
            
            foreach ($imageUrls as $imageUrl) {
                // Invalidar cache específico por imagen
                $cacheKey = 'image_cache_' . md5($imageUrl);
                Cache::forget($cacheKey);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Cache de imágenes invalidado',
                'images_processed' => count($imageUrls)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al invalidar cache de imágenes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar timestamp para cache busting
     */
    public function getCacheBustingTimestamp(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'timestamp' => time(),
            'formatted' => date('Y-m-d H:i:s')
        ])->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    /**
     * Obtener información del estado del cache
     */
    public function getCacheStatus(): JsonResponse
    {
        try {
            $status = [
                'cache_driver' => config('cache.default'),
                'cache_prefix' => config('cache.prefix'),
                'timestamp' => time(),
                'server_time' => date('Y-m-d H:i:s'),
                'uptime' => $this->getServerUptime()
            ];

            return response()->json([
                'success' => true,
                'data' => $status
            ])->withHeaders([
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado del cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener uptime del servidor
     */
    private function getServerUptime(): string
    {
        try {
            if (function_exists('shell_exec')) {
                $uptime = shell_exec('uptime -p');
                return trim($uptime ?: 'No disponible');
            }
            return 'No disponible';
        } catch (\Exception $e) {
            return 'No disponible';
        }
    }
}
