<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockReservation;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockReservationService
{
    /**
     * Crear reserva de stock
     */
    public function createReservation(
        string $productId,
        int $quantity,
        ?string $orderId = null,
        ?string $userId = null,
        ?string $sessionId = null,
        int $expirationMinutes = 30,
        array $metadata = []
    ): array {
        try {
            DB::beginTransaction();

            // Verificar que el producto existe
            $product = Product::find($productId);
            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ];
            }

            // Verificar stock disponible
            if (!$this->hasAvailableStock($productId, $quantity)) {
                return [
                    'success' => false,
                    'message' => 'Stock insuficiente para la cantidad solicitada'
                ];
            }

            // Crear la reserva
            $reservation = StockReservation::create([
                'product_id' => $productId,
                'order_id' => $orderId,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'quantity' => $quantity,
                'status' => 'pending',
                'expires_at' => now()->addMinutes($expirationMinutes),
                'metadata' => $metadata,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Reserva creada correctamente',
                'data' => [
                    'reservation_id' => $reservation->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'expires_at' => $reservation->expires_at->toISOString(),
                    'available_stock' => $this->getAvailableStock($productId),
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear reserva de stock: ' . $e->getMessage(), [
                'product_id' => $productId,
                'quantity' => $quantity,
                'order_id' => $orderId,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Error al crear reserva de stock: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Confirmar reserva y ajustar stock
     */
    public function confirmReservation(string $reservationId): array
    {
        try {
            DB::beginTransaction();

            $reservation = StockReservation::find($reservationId);
            if (!$reservation) {
                return [
                    'success' => false,
                    'message' => 'Reserva no encontrada'
                ];
            }

            if (!$reservation->isActive()) {
                return [
                    'success' => false,
                    'message' => 'La reserva no está activa'
                ];
            }

            $product = $reservation->product;
            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ];
            }

            // Verificar stock disponible nuevamente
            if (!$this->hasAvailableStock($product->id, $reservation->quantity)) {
                return [
                    'success' => false,
                    'message' => 'Stock insuficiente para confirmar la reserva'
                ];
            }

            // Ajustar stock del producto
            if (!$product->decrementStock($reservation->quantity)) {
                return [
                    'success' => false,
                    'message' => 'Error al ajustar stock del producto'
                ];
            }

            // Confirmar la reserva
            $reservation->confirm();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Reserva confirmada y stock ajustado correctamente',
                'data' => [
                    'reservation_id' => $reservation->id,
                    'product_id' => $product->id,
                    'quantity' => $reservation->quantity,
                    'new_stock' => $product->fresh()->stock_quantity,
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al confirmar reserva: ' . $e->getMessage(), [
                'reservation_id' => $reservationId,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Error al confirmar reserva: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancelar reserva
     */
    public function cancelReservation(string $reservationId): array
    {
        try {
            $reservation = StockReservation::find($reservationId);
            if (!$reservation) {
                return [
                    'success' => false,
                    'message' => 'Reserva no encontrada'
                ];
            }

            $reservation->cancel();

            return [
                'success' => true,
                'message' => 'Reserva cancelada correctamente',
                'data' => [
                    'reservation_id' => $reservation->id,
                    'available_stock' => $this->getAvailableStock($reservation->product_id),
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error al cancelar reserva: ' . $e->getMessage(), [
                'reservation_id' => $reservationId,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Error al cancelar reserva: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reservar stock para un pedido completo
     */
    public function reserveStockForOrder(Order $order, int $expirationMinutes = 30): array
    {
        try {
            DB::beginTransaction();

            $reservations = [];
            $errors = [];

            foreach ($order->items as $item) {
                $result = $this->createReservation(
                    $item->product_id,
                    $item->quantity,
                    $order->id,
                    $order->user_id,
                    null,
                    $expirationMinutes,
                    [
                        'order_item_id' => $item->id,
                        'price' => $item->price,
                    ]
                );

                if ($result['success']) {
                    $reservations[] = $result['data'];
                } else {
                    $errors[] = [
                        'product_id' => $item->product_id,
                        'error' => $result['message']
                    ];
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Error al reservar stock para algunos productos',
                    'errors' => $errors
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Stock reservado correctamente para el pedido',
                'data' => [
                    'order_id' => $order->id,
                    'reservations' => $reservations,
                    'total_reservations' => count($reservations),
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al reservar stock para pedido: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Error al reservar stock para el pedido: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Confirmar todas las reservas de un pedido
     */
    public function confirmOrderReservations(string $orderId): array
    {
        try {
            DB::beginTransaction();

            $reservations = StockReservation::forOrder($orderId)
                ->where('status', 'pending')
                ->get();

            if ($reservations->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No hay reservas pendientes para este pedido'
                ];
            }

            $confirmed = 0;
            $errors = [];

            foreach ($reservations as $reservation) {
                $result = $this->confirmReservation($reservation->id);
                
                if ($result['success']) {
                    $confirmed++;
                } else {
                    $errors[] = [
                        'reservation_id' => $reservation->id,
                        'error' => $result['message']
                    ];
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Error al confirmar algunas reservas',
                    'errors' => $errors
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Se confirmaron {$confirmed} reservas correctamente",
                'data' => [
                    'order_id' => $orderId,
                    'confirmed_reservations' => $confirmed,
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al confirmar reservas del pedido: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Error al confirmar reservas del pedido: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancelar todas las reservas de un pedido
     */
    public function cancelOrderReservations(string $orderId): array
    {
        try {
            $cancelled = StockReservation::forOrder($orderId)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            return [
                'success' => true,
                'message' => "Se cancelaron {$cancelled} reservas",
                'data' => [
                    'order_id' => $orderId,
                    'cancelled_reservations' => $cancelled,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error al cancelar reservas del pedido: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Error al cancelar reservas del pedido: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Limpiar reservas expiradas
     */
    public function cleanExpiredReservations(): array
    {
        try {
            $cleaned = StockReservation::cleanExpiredReservations();

            return [
                'success' => true,
                'message' => "Se limpiaron {$cleaned} reservas expiradas",
                'data' => [
                    'cleaned_reservations' => $cleaned,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error al limpiar reservas expiradas: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Error al limpiar reservas expiradas: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener stock disponible considerando reservas
     */
    public function getAvailableStock(string $productId): int
    {
        return StockReservation::getAvailableStock($productId);
    }

    /**
     * Verificar si hay stock disponible
     */
    public function hasAvailableStock(string $productId, int $quantity): bool
    {
        return StockReservation::hasAvailableStock($productId, $quantity);
    }

    /**
     * Obtener cantidad reservada
     */
    public function getReservedQuantity(string $productId): int
    {
        return StockReservation::getReservedQuantity($productId);
    }

    /**
     * Obtener estadísticas de reservas
     */
    public function getReservationStats(): array
    {
        $totalReservations = StockReservation::count();
        $activeReservations = StockReservation::active()->count();
        $expiredReservations = StockReservation::expired()->count();
        $confirmedReservations = StockReservation::where('status', 'confirmed')->count();
        $cancelledReservations = StockReservation::where('status', 'cancelled')->count();

        return [
            'total' => $totalReservations,
            'active' => $activeReservations,
            'expired' => $expiredReservations,
            'confirmed' => $confirmedReservations,
            'cancelled' => $cancelledReservations,
        ];
    }

    /**
     * Obtener reservas por producto
     */
    public function getProductReservations(string $productId): array
    {
        $reservations = StockReservation::forProduct($productId)
            ->with(['order', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'success' => true,
            'data' => [
                'product_id' => $productId,
                'reservations' => $reservations->map(function ($reservation) {
                    return [
                        'id' => $reservation->id,
                        'quantity' => $reservation->quantity,
                        'status' => $reservation->status,
                        'expires_at' => $reservation->expires_at?->toISOString(),
                        'order_id' => $reservation->order_id,
                        'user_id' => $reservation->user_id,
                        'created_at' => $reservation->created_at->toISOString(),
                    ];
                }),
                'total_reservations' => $reservations->count(),
                'active_reservations' => $reservations->where('status', 'pending')->count(),
            ]
        ];
    }
} 