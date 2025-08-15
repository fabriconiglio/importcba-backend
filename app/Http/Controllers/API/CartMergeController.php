<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CartMergeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Cart Merge",
 *     description="Endpoints para fusión de carritos anónimos con carritos de usuarios registrados"
 * )
 */
class CartMergeController extends Controller
{
    private CartMergeService $cartMergeService;

    public function __construct(CartMergeService $cartMergeService)
    {
        $this->cartMergeService = $cartMergeService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cart-merge/merge",
     *     summary="Mergear carrito anónimo con carrito del usuario",
     *     description="Fusiona el carrito de compras anónimo con el carrito del usuario autenticado",
     *     tags={"Cart Merge"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_id"},
     *             @OA\Property(property="session_id", type="string", example="session_1234567890", description="ID de sesión del carrito anónimo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Carrito fusionado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Carrito fusionado correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="merged_items", type="integer", example=3, description="Número de items fusionados"),
     *                 @OA\Property(property="conflicts", type="integer", example=1, description="Número de conflictos resueltos"),
     *                 @OA\Property(
     *                     property="conflict_details",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="product_id", type="string", format="uuid"),
     *                         @OA\Property(property="anonymous_quantity", type="integer", example=2),
     *                         @OA\Property(property="user_quantity", type="integer", example=1),
     *                         @OA\Property(property="final_quantity", type="integer", example=3),
     *                         @OA\Property(property="resolution", type="string", example="sum", description="sum|max|min|replace")
     *                     )
     *                 ),
     *                 @OA\Property(property="user_cart_id", type="string", format="uuid", description="ID del carrito del usuario")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/cart-merge/stats",
     *     summary="Obtener estadísticas de carritos anónimos",
     *     description="Obtiene estadísticas sobre carritos anónimos (solo administradores)",
     *     tags={"Cart Merge"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Estadísticas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Estadísticas obtenidas correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_anonymous_carts", type="integer", example=150, description="Total de carritos anónimos"),
     *                 @OA\Property(property="active_carts", type="integer", example=45, description="Carritos activos (no expirados)"),
     *                 @OA\Property(property="expired_carts", type="integer", example=105, description="Carritos expirados"),
     *                 @OA\Property(property="total_items", type="integer", example=320, description="Total de items en carritos anónimos"),
     *                 @OA\Property(property="average_items_per_cart", type="number", format="float", example=2.13, description="Promedio de items por carrito"),
     *                 @OA\Property(property="total_value", type="number", format="float", example=15420.50, description="Valor total de carritos anónimos"),
     *                 @OA\Property(property="average_cart_value", type="number", format="float", example=102.80, description="Valor promedio por carrito"),
     *                 @OA\Property(property="carts_created_today", type="integer", example=12, description="Carritos creados hoy"),
     *                 @OA\Property(property="carts_expired_today", type="integer", example=8, description="Carritos expirados hoy"),
     *                 @OA\Property(property="merge_success_rate", type="number", format="float", example=0.85, description="Tasa de éxito en fusiones (0-1)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Acceso denegado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/cart-merge/clean-expired",
     *     summary="Limpiar carritos anónimos expirados",
     *     description="Elimina todos los carritos anónimos que han expirado (solo administradores)",
     *     tags={"Cart Merge"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Carritos expirados eliminados exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Se eliminaron 25 carritos anónimos expirados"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="deleted_carts", type="integer", example=25, description="Número de carritos eliminados")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Acceso denegado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/cart-merge/anonymous-info",
     *     summary="Obtener información del carrito anónimo",
     *     description="Obtiene información básica sobre un carrito anónimo específico",
     *     tags={"Cart Merge"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="session_id",
     *         in="query",
     *         description="ID de sesión del carrito anónimo",
     *         required=true,
     *         @OA\Schema(type="string", example="session_1234567890")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Información del carrito anónimo obtenida"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="exists", type="boolean", example=true, description="Si el carrito existe"),
     *                 @OA\Property(property="cart_id", type="string", format="uuid", nullable=true, description="ID del carrito (si existe)"),
     *                 @OA\Property(property="total_items", type="integer", example=3, description="Total de items en el carrito"),
     *                 @OA\Property(property="total", type="number", format="float", example=89.97, description="Total monetario del carrito"),
     *                 @OA\Property(property="total_savings", type="number", format="float", example=20.00, description="Total de ahorros por descuentos"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", nullable=true, example="2024-12-31T23:59:59Z", description="Fecha de expiración del carrito")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
