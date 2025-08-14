<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Services\StockReservationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Stock Reservations",
 *     description="Endpoints para gestión de reservas de stock"
 * )
 */
class StockReservationController extends Controller
{
    private StockReservationService $stockReservationService;

    public function __construct(StockReservationService $stockReservationService)
    {
        $this->stockReservationService = $stockReservationService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/stock-reservations",
     *     summary="Crear reserva de stock",
     *     description="Crea una nueva reserva de stock para un producto específico",
     *     tags={"Stock Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id","quantity"},
     *             @OA\Property(property="product_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID del producto"),
     *             @OA\Property(property="quantity", type="integer", minimum=1, example=2, description="Cantidad a reservar"),
     *             @OA\Property(property="order_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001", description="ID del pedido (opcional)"),
     *             @OA\Property(property="user_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440002", description="ID del usuario (opcional)"),
     *             @OA\Property(property="session_id", type="string", example="session_123456", description="ID de sesión (opcional)"),
     *             @OA\Property(property="expiration_minutes", type="integer", minimum=1, maximum=1440, example=30, description="Minutos hasta expiración (máximo 24 horas)"),
     *             @OA\Property(property="metadata", type="object", example={"source": "cart", "priority": "high"}, description="Metadatos adicionales (opcional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reserva creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reserva creada correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="reservation_id", type="string", format="uuid"),
     *                 @OA\Property(property="product_id", type="string", format="uuid"),
     *                 @OA\Property(property="quantity", type="integer", example=2),
     *                 @OA\Property(property="expires_at", type="string", format="date-time"),
     *                 @OA\Property(property="status", type="string", example="active")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al crear reserva",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Stock insuficiente para reservar")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|string|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'order_id' => 'nullable|string|exists:orders,id',
                'user_id' => 'nullable|string|exists:users,id',
                'session_id' => 'nullable|string',
                'expiration_minutes' => 'nullable|integer|min:1|max:1440', // Máximo 24 horas
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->stockReservationService->createReservation(
                $request->product_id,
                $request->quantity,
                $request->order_id,
                $request->user_id,
                $request->session_id,
                $request->expiration_minutes ?? 30,
                $request->metadata ?? []
            );

            return response()->json($result, $result['success'] ? 201 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear reserva: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/stock-reservations/{reservationId}/confirm",
     *     summary="Confirmar reserva y ajustar stock",
     *     description="Confirma una reserva de stock y ajusta el inventario del producto",
     *     tags={"Stock Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reservationId",
     *         in="path",
     *         description="ID de la reserva",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reserva confirmada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reserva confirmada correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="reservation_id", type="string", format="uuid"),
     *                 @OA\Property(property="product_id", type="string", format="uuid"),
     *                 @OA\Property(property="quantity", type="integer", example=2),
     *                 @OA\Property(property="status", type="string", example="confirmed"),
     *                 @OA\Property(property="stock_adjusted", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al confirmar reserva",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="La reserva ya expiró o no existe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reserva no encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function confirm(Request $request, string $reservationId): JsonResponse
    {
        try {
            $result = $this->stockReservationService->confirmReservation($reservationId);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar reserva: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/stock-reservations/{reservationId}/cancel",
     *     summary="Cancelar reserva",
     *     description="Cancela una reserva de stock y libera el inventario reservado",
     *     tags={"Stock Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reservationId",
     *         in="path",
     *         description="ID de la reserva",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reserva cancelada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reserva cancelada correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="reservation_id", type="string", format="uuid"),
     *                 @OA\Property(property="product_id", type="string", format="uuid"),
     *                 @OA\Property(property="quantity", type="integer", example=2),
     *                 @OA\Property(property="status", type="string", example="cancelled"),
     *                 @OA\Property(property="stock_released", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al cancelar reserva",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="La reserva ya fue confirmada o cancelada")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reserva no encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function cancel(Request $request, string $reservationId): JsonResponse
    {
        try {
            $result = $this->stockReservationService->cancelReservation($reservationId);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar reserva: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/stock-reservations/order/{orderId}/reserve",
     *     summary="Reservar stock para un pedido completo",
     *     description="Crea reservas de stock para todos los productos de un pedido",
     *     tags={"Stock Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         description="ID del pedido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="expiration_minutes", type="integer", minimum=1, maximum=1440, example=30, description="Minutos hasta expiración (máximo 24 horas)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Stock reservado para el pedido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Stock reservado para el pedido correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="order_id", type="string", format="uuid"),
     *                 @OA\Property(property="reservations_created", type="integer", example=3),
     *                 @OA\Property(property="total_quantity", type="integer", example=5),
     *                 @OA\Property(
     *                     property="reservations",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="reservation_id", type="string", format="uuid"),
     *                         @OA\Property(property="product_id", type="string", format="uuid"),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="expires_at", type="string", format="date-time")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al reservar stock",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Stock insuficiente para algunos productos")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pedido no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function reserveForOrder(Request $request, string $orderId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'expiration_minutes' => 'nullable|integer|min:1|max:1440',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $order = Order::findOrFail($orderId);
            $result = $this->stockReservationService->reserveStockForOrder(
                $order,
                $request->expiration_minutes ?? 30
            );

            return response()->json($result, $result['success'] ? 201 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reservar stock para pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/stock-reservations/order/{orderId}/confirm",
     *     summary="Confirmar todas las reservas de un pedido",
     *     description="Confirma todas las reservas de stock asociadas a un pedido específico",
     *     tags={"Stock Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         description="ID del pedido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reservas del pedido confirmadas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reservas del pedido confirmadas correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="order_id", type="string", format="uuid"),
     *                 @OA\Property(property="reservations_confirmed", type="integer", example=3),
     *                 @OA\Property(property="total_quantity", type="integer", example=5),
     *                 @OA\Property(property="stock_adjusted", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al confirmar reservas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Algunas reservas ya expiraron")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pedido no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function confirmOrderReservations(Request $request, string $orderId): JsonResponse
    {
        try {
            $result = $this->stockReservationService->confirmOrderReservations($orderId);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar reservas del pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/stock-reservations/order/{orderId}/cancel",
     *     summary="Cancelar todas las reservas de un pedido",
     *     description="Cancela todas las reservas de stock asociadas a un pedido específico",
     *     tags={"Stock Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         description="ID del pedido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reservas del pedido canceladas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reservas del pedido canceladas correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="order_id", type="string", format="uuid"),
     *                 @OA\Property(property="reservations_cancelled", type="integer", example=3),
     *                 @OA\Property(property="total_quantity", type="integer", example=5),
     *                 @OA\Property(property="stock_released", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al cancelar reservas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Algunas reservas ya fueron confirmadas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pedido no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function cancelOrderReservations(Request $request, string $orderId): JsonResponse
    {
        try {
            $result = $this->stockReservationService->cancelOrderReservations($orderId);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar reservas del pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/stock-reservations/product/{productId}/available",
     *     summary="Obtener stock disponible de un producto",
     *     description="Obtiene información detallada del stock disponible de un producto específico",
     *     tags={"Stock Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         description="ID del producto",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stock disponible obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Stock disponible obtenido correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="product_id", type="string", format="uuid"),
     *                 @OA\Property(property="product_name", type="string", example="Producto Ejemplo"),
     *                 @OA\Property(property="total_stock", type="integer", example=100),
     *                 @OA\Property(property="reserved_quantity", type="integer", example=15),
     *                 @OA\Property(property="available_stock", type="integer", example=85)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Producto no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function getAvailableStock(Request $request, string $productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            $availableStock = $this->stockReservationService->getAvailableStock($productId);
            $reservedQuantity = $this->stockReservationService->getReservedQuantity($productId);

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'total_stock' => $product->stock_quantity,
                    'reserved_quantity' => $reservedQuantity,
                    'available_stock' => $availableStock,
                ],
                'message' => 'Stock disponible obtenido correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener stock disponible: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/stock-reservations/check-availability",
     *     summary="Verificar disponibilidad de stock",
     *     description="Verifica si hay stock disponible para una cantidad específica de un producto",
     *     tags={"Stock Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id","quantity"},
     *             @OA\Property(property="product_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID del producto"),
     *             @OA\Property(property="quantity", type="integer", minimum=1, example=5, description="Cantidad a verificar")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Disponibilidad verificada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Stock disponible"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="product_id", type="string", format="uuid"),
     *                 @OA\Property(property="product_name", type="string", example="Producto Ejemplo"),
     *                 @OA\Property(property="requested_quantity", type="integer", example=5),
     *                 @OA\Property(property="total_stock", type="integer", example=100),
     *                 @OA\Property(property="available_stock", type="integer", example=85),
     *                 @OA\Property(property="has_available_stock", type="boolean", example=true),
     *                 @OA\Property(property="can_reserve", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Producto no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|string|exists:products,id',
                'quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $product = Product::findOrFail($request->product_id);
            $availableStock = $this->stockReservationService->getAvailableStock($request->product_id);
            $hasStock = $this->stockReservationService->hasAvailableStock($request->product_id, $request->quantity);

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $request->product_id,
                    'product_name' => $product->name,
                    'requested_quantity' => $request->quantity,
                    'total_stock' => $product->stock_quantity,
                    'available_stock' => $availableStock,
                    'has_available_stock' => $hasStock,
                    'can_reserve' => $hasStock,
                ],
                'message' => $hasStock ? 'Stock disponible' : 'Stock insuficiente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar disponibilidad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/stock-reservations/product/{productId}/reservations",
     *     summary="Obtener reservas de un producto",
     *     description="Obtiene todas las reservas activas de un producto específico",
     *     tags={"Stock Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         description="ID del producto",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reservas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reservas obtenidas correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="reservation_id", type="string", format="uuid"),
     *                     @OA\Property(property="product_id", type="string", format="uuid"),
     *                     @OA\Property(property="quantity", type="integer", example=2),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="expires_at", type="string", format="date-time"),
     *                     @OA\Property(property="order_id", type="string", format="uuid", nullable=true),
     *                     @OA\Property(property="user_id", type="string", format="uuid", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Producto no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function getProductReservations(Request $request, string $productId): JsonResponse
    {
        try {
            $result = $this->stockReservationService->getProductReservations($productId);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reservas del producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/stock-reservations/stats",
     *     summary="Obtener estadísticas de reservas",
     *     description="Obtiene estadísticas detalladas de las reservas de stock (solo administradores)",
     *     tags={"Stock Reservations"},
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
     *                 @OA\Property(property="total_reservations", type="integer", example=150),
     *                 @OA\Property(property="active_reservations", type="integer", example=45),
     *                 @OA\Property(property="expired_reservations", type="integer", example=25),
     *                 @OA\Property(property="confirmed_reservations", type="integer", example=60),
     *                 @OA\Property(property="cancelled_reservations", type="integer", example=20),
     *                 @OA\Property(property="total_quantity_reserved", type="integer", example=300),
     *                 @OA\Property(property="total_quantity_confirmed", type="integer", example=120),
     *                 @OA\Property(property="total_quantity_cancelled", type="integer", example=40),
     *                 @OA\Property(property="average_reservation_duration", type="number", format="float", example=25.5),
     *                 @OA\Property(property="reservations_today", type="integer", example=15),
     *                 @OA\Property(property="reservations_this_week", type="integer", example=85),
     *                 @OA\Property(property="reservations_this_month", type="integer", example=150)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado'
                ], 403);
            }

            $stats = $this->stockReservationService->getReservationStats();

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
     *     path="/api/v1/stock-reservations/clean-expired",
     *     summary="Limpiar reservas expiradas",
     *     description="Elimina todas las reservas de stock que han expirado (solo administradores)",
     *     tags={"Stock Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Reservas expiradas limpiadas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reservas expiradas limpiadas correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="reservations_cleaned", type="integer", example=25),
     *                 @OA\Property(property="total_quantity_released", type="integer", example=50),
     *                 @OA\Property(property="stock_released", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al limpiar reservas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al limpiar reservas expiradas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado",
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
            
            if (!$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado'
                ], 403);
            }

            $result = $this->stockReservationService->cleanExpiredReservations();

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar reservas expiradas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/stock-reservations/{reservationId}/extend",
     *     summary="Extender expiración de una reserva",
     *     description="Extiende el tiempo de expiración de una reserva de stock específica",
     *     tags={"Stock Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reservationId",
     *         in="path",
     *         description="ID de la reserva",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"minutes"},
     *             @OA\Property(property="minutes", type="integer", minimum=1, maximum=1440, example=30, description="Minutos adicionales para extender (máximo 24 horas)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Expiración extendida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expiración extendida correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="reservation_id", type="string", format="uuid"),
     *                 @OA\Property(property="new_expires_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al extender expiración",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="La reserva ya expiró o no existe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reserva no encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function extendExpiration(Request $request, string $reservationId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'minutes' => 'required|integer|min:1|max:1440',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $reservation = \App\Models\StockReservation::findOrFail($reservationId);
            $reservation->extendExpiration($request->minutes);

            return response()->json([
                'success' => true,
                'data' => [
                    'reservation_id' => $reservation->id,
                    'new_expires_at' => $reservation->fresh()->expires_at->toISOString(),
                ],
                'message' => 'Expiración extendida correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al extender expiración: ' . $e->getMessage()
            ], 500);
        }
    }
}
