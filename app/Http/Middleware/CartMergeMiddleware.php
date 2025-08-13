<?php

namespace App\Http\Middleware;

use App\Services\CartMergeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CartMergeMiddleware
{
    private CartMergeService $cartMergeService;

    public function __construct(CartMergeService $cartMergeService)
    {
        $this->cartMergeService = $cartMergeService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo procesar después de una respuesta exitosa
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $this->processCartMerge($request, $response);
        }

        return $response;
    }

    /**
     * Procesar el merge del carrito si es necesario
     */
    private function processCartMerge(Request $request, Response $response): void
    {
        try {
            // Solo procesar en rutas de autenticación exitosa
            if (!$this->isSuccessfulAuthRoute($request)) {
                return;
            }

            $user = $request->user();
            if (!$user) {
                return;
            }

            // Obtener session_id del header o cookie
            $sessionId = $request->header('X-Session-ID') ?? $request->cookie('anonymous_session_id');
            
            if (!$sessionId) {
                return;
            }

            // Verificar si hay carrito anónimo para mergear
            $anonymousCart = $this->cartMergeService->getAnonymousCart($sessionId);
            
            if (!$anonymousCart || $anonymousCart->isEmpty()) {
                return;
            }

            // Realizar el merge
            $result = $this->cartMergeService->mergeAnonymousCart($user, $sessionId);

            // Agregar información del merge a la respuesta
            if ($result['success'] && $result['merged_items'] > 0) {
                $this->addMergeInfoToResponse($response, $result);
            }

        } catch (\Exception $e) {
            // Log del error pero no interrumpir la respuesta
            Log::error('Error en CartMergeMiddleware: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'session_id' => $request->header('X-Session-ID'),
                'exception' => $e
            ]);
        }
    }

    /**
     * Verificar si es una ruta de autenticación exitosa
     */
    private function isSuccessfulAuthRoute(Request $request): bool
    {
        $authRoutes = [
            'api/v1/login',
            'api/v1/register',
            'api/v1/auth/login',
            'api/v1/auth/register',
        ];

        $currentRoute = $request->path();
        
        foreach ($authRoutes as $authRoute) {
            if (str_contains($currentRoute, $authRoute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Agregar información del merge a la respuesta
     */
    private function addMergeInfoToResponse(Response $response, array $mergeResult): void
    {
        try {
            $content = json_decode($response->getContent(), true);
            
            if (is_array($content)) {
                $content['cart_merge'] = [
                    'merged_items' => $mergeResult['merged_items'],
                    'conflicts' => $mergeResult['conflicts'],
                    'message' => $mergeResult['message'],
                ];

                $response->setContent(json_encode($content));
            }
        } catch (\Exception $e) {
            // Si no se puede modificar la respuesta, solo log
            Log::info('No se pudo agregar información de merge a la respuesta', [
                'merge_result' => $mergeResult
            ]);
        }
    }
}
