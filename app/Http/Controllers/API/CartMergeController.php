<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CartMergeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CartMergeController extends Controller
{
    private CartMergeService $cartMergeService;

    public function __construct(CartMergeService $cartMergeService)
    {
        $this->cartMergeService = $cartMergeService;
    }

    /**
     * Mergear carrito anónimo con carrito del usuario
     */
    public function merge(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $sessionId = $request->session_id;

            // Realizar el merge
            $result = $this->cartMergeService->mergeAnonymousCart($user, $sessionId);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'merged_items' => $result['merged_items'],
                    'conflicts' => $result['conflicts'],
                    'conflict_details' => $result['conflict_details'] ?? [],
                    'user_cart_id' => $result['user_cart_id'],
                ],
                'message' => $result['message']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mergear carrito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de carritos anónimos
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Solo administradores pueden ver estadísticas
            if (!$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado'
                ], 403);
            }

            $stats = $this->cartMergeService->getAnonymousCartStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar carritos anónimos expirados
     */
    public function cleanExpired(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Solo administradores pueden limpiar carritos
            if (!$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado'
                ], 403);
            }

            $deletedCount = $this->cartMergeService->cleanExpiredAnonymousCarts();

            return response()->json([
                'success' => true,
                'data' => [
                    'deleted_carts' => $deletedCount
                ],
                'message' => "Se eliminaron {$deletedCount} carritos anónimos expirados"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar carritos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información del carrito anónimo
     */
    public function getAnonymousCartInfo(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sessionId = $request->session_id;
            $cart = $this->cartMergeService->getAnonymousCart($sessionId);

            if (!$cart) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'exists' => false,
                        'total_items' => 0,
                        'total' => 0,
                    ],
                    'message' => 'Carrito anónimo no encontrado'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'exists' => true,
                    'cart_id' => $cart->id,
                    'total_items' => $cart->getTotalItems(),
                    'total' => $cart->getTotal(),
                    'total_savings' => $cart->getTotalSavings(),
                    'expires_at' => $cart->expires_at?->toISOString(),
                ],
                'message' => 'Información del carrito anónimo obtenida'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información: ' . $e->getMessage()
            ], 500);
        }
    }
}
