<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * @OA\Tag(
 *     name="Documentación",
 *     description="Endpoints para gestión de documentación de la API"
 * )
 */
class DocumentationController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/docs",
     *     summary="Obtener documentación de la API",
     *     description="Retorna información sobre la documentación de la API",
     *     tags={"Documentación"},
     *     @OA\Response(
     *         response=200,
     *         description="Información de documentación obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Información de documentación obtenida"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="swagger_url", type="string", example="/api/docs"),
     *                 @OA\Property(property="json_url", type="string", example="/docs/api-docs.json"),
     *                 @OA\Property(property="version", type="string", example="1.0.0"),
     *                 @OA\Property(property="title", type="string", example="Ecommerce API Documentation"),
     *                 @OA\Property(property="description", type="string", example="API completa para el sistema de ecommerce"),
     *                 @OA\Property(
     *                     property="endpoints",
     *                     type="object",
     *                     @OA\Property(property="authentication", type="integer", example=5),
     *                     @OA\Property(property="products", type="integer", example=8),
     *                     @OA\Property(property="catalog", type="integer", example=3),
     *                     @OA\Property(property="cart", type="integer", example=6),
     *                     @OA\Property(property="orders", type="integer", example=7),
     *                     @OA\Property(property="payments", type="integer", example=4),
     *                     @OA\Property(property="coupons", type="integer", example=5),
     *                     @OA\Property(property="emails", type="integer", example=9),
     *                     @OA\Property(property="stock", type="integer", example=12)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $data = [
            'swagger_url' => '/api/docs',
            'json_url' => '/docs/api-docs.json',
            'version' => '1.0.0',
            'title' => 'Ecommerce API Documentation',
            'description' => 'API completa para el sistema de ecommerce con autenticación, productos, carrito, pedidos, pagos, cupones y más.',
            'endpoints' => [
                'authentication' => 5,
                'products' => 8,
                'catalog' => 3,
                'cart' => 6,
                'orders' => 7,
                'payments' => 4,
                'coupons' => 5,
                'emails' => 9,
                'stock' => 12
            ]
        ];

        return $this->successResponse($data, 'Información de documentación obtenida');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/docs/health",
     *     summary="Verificar estado de la API",
     *     description="Verifica el estado general de la API y sus servicios",
     *     tags={"Documentación"},
     *     @OA\Response(
     *         response=200,
     *         description="Estado de la API verificado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="API funcionando correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="status", type="string", example="healthy"),
     *                 @OA\Property(property="timestamp", type="string", format="date-time"),
     *                 @OA\Property(property="version", type="string", example="1.0.0"),
     *                 @OA\Property(property="environment", type="string", example="local"),
     *                 @OA\Property(
     *                     property="services",
     *                     type="object",
     *                     @OA\Property(property="database", type="string", example="connected"),
     *                     @OA\Property(property="cache", type="string", example="connected"),
     *                     @OA\Property(property="queue", type="string", example="connected"),
     *                     @OA\Property(property="mail", type="string", example="configured")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function health(Request $request): JsonResponse
    {
        try {
            // Verificar conexión a base de datos
            $dbStatus = 'connected';
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                $dbStatus = 'disconnected';
            }

            // Verificar cache
            $cacheStatus = 'connected';
            try {
                Cache::store()->has('health_check');
            } catch (\Exception $e) {
                $cacheStatus = 'disconnected';
            }

            // Verificar cola
            $queueStatus = 'connected';
            try {
                // Verificar si la tabla de colas existe
                DB::table('jobs')->count();
            } catch (\Exception $e) {
                $queueStatus = 'disconnected';
            }

            // Verificar configuración de email
            $mailStatus = 'configured';
            if (config('mail.default') === 'log') {
                $mailStatus = 'log_mode';
            }

            $data = [
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'version' => '1.0.0',
                'environment' => config('app.env'),
                'services' => [
                    'database' => $dbStatus,
                    'cache' => $cacheStatus,
                    'queue' => $queueStatus,
                    'mail' => $mailStatus
                ]
            ];

            return $this->successResponse($data, 'API funcionando correctamente');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Error al verificar estado de la API: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/docs/endpoints",
     *     summary="Listar endpoints disponibles",
     *     description="Obtiene una lista de todos los endpoints disponibles en la API",
     *     tags={"Documentación"},
     *     @OA\Response(
     *         response=200,
     *         description="Endpoints listados exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Endpoints listados exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="method", type="string", example="GET"),
     *                     @OA\Property(property="path", type="string", example="/api/v1/products"),
     *                     @OA\Property(property="name", type="string", example="products.index"),
     *                     @OA\Property(property="description", type="string", example="Listar productos"),
     *                     @OA\Property(property="auth_required", type="boolean", example=false)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function endpoints(Request $request): JsonResponse
    {
        try {
            $routes = Route::getRoutes();
            $endpoints = [];

            foreach ($routes as $route) {
                if (str_starts_with($route->uri(), 'api/v1/')) {
                    $endpoints[] = [
                        'method' => $route->methods()[0],
                        'path' => '/' . $route->uri(),
                        'name' => $route->getName() ?? 'unnamed',
                        'description' => $this->getRouteDescription($route),
                        'auth_required' => in_array('auth:sanctum', $route->middleware())
                    ];
                }
            }

            return $this->successResponse($endpoints, 'Endpoints listados exitosamente');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Error al obtener endpoints: ' . $e->getMessage());
        }
    }

    /**
     * Obtener descripción de una ruta
     */
    private function getRouteDescription($route): string
    {
        $uri = $route->uri();
        
        if (str_contains($uri, 'products')) {
            return 'Gestión de productos';
        } elseif (str_contains($uri, 'categories')) {
            return 'Gestión de categorías';
        } elseif (str_contains($uri, 'brands')) {
            return 'Gestión de marcas';
        } elseif (str_contains($uri, 'cart')) {
            return 'Gestión del carrito';
        } elseif (str_contains($uri, 'orders')) {
            return 'Gestión de pedidos';
        } elseif (str_contains($uri, 'payments')) {
            return 'Gestión de pagos';
        } elseif (str_contains($uri, 'coupons')) {
            return 'Gestión de cupones';
        } elseif (str_contains($uri, 'emails')) {
            return 'Gestión de emails';
        } elseif (str_contains($uri, 'stock')) {
            return 'Gestión de stock';
        } elseif (str_contains($uri, 'auth')) {
            return 'Autenticación';
        } else {
            return 'Endpoint de la API';
        }
    }
}
