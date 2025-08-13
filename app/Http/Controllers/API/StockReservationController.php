<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Services\StockReservationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class StockReservationController extends Controller
{
    private StockReservationService $stockReservationService;

    public function __construct(StockReservationService $stockReservationService)
    {
        $this->stockReservationService = $stockReservationService;
    }

    /**
     * Crear reserva de stock
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
     * Confirmar reserva y ajustar stock
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
     * Cancelar reserva
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
     * Reservar stock para un pedido completo
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
     * Confirmar todas las reservas de un pedido
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
     * Cancelar todas las reservas de un pedido
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
     * Obtener stock disponible de un producto
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
     * Verificar disponibilidad de stock
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
     * Obtener reservas de un producto
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
     * Obtener estadísticas de reservas (solo admin)
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
     * Limpiar reservas expiradas (solo admin)
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
     * Extender expiración de una reserva
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
