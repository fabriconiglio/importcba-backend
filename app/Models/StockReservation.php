<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReservation extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_id',
        'order_id',
        'user_id',
        'session_id',
        'quantity',
        'status',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    // =============================================
    // RELACIONES
    // =============================================

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =============================================
    // SCOPES
    // =============================================

    /**
     * Scope para reservas activas (no expiradas y no canceladas)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'pending')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope para reservas por producto
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope para reservas por orden
     */
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope para reservas por usuario
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para reservas por sesión
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope para reservas expiradas
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    // =============================================
    // MÉTODOS DE UTILIDAD
    // =============================================

    /**
     * Verificar si la reserva está activa
     */
    public function isActive(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isFuture();
    }

    /**
     * Verificar si la reserva ha expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Verificar si la reserva está confirmada
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Verificar si la reserva está cancelada
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Confirmar la reserva
     */
    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);
    }

    /**
     * Cancelar la reserva
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Marcar como expirada
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Extender la expiración
     */
    public function extendExpiration(int $minutes = 30): void
    {
        $this->update(['expires_at' => now()->addMinutes($minutes)]);
    }

    /**
     * Obtener el stock disponible considerando reservas activas
     */
    public static function getAvailableStock(string $productId): int
    {
        $product = Product::find($productId);
        if (!$product) {
            return 0;
        }

        $reservedQuantity = self::forProduct($productId)
            ->active()
            ->sum('quantity');

        return max(0, $product->stock_quantity - $reservedQuantity);
    }

    /**
     * Verificar si hay stock disponible para una cantidad
     */
    public static function hasAvailableStock(string $productId, int $quantity): bool
    {
        return self::getAvailableStock($productId) >= $quantity;
    }

    /**
     * Obtener cantidad reservada para un producto
     */
    public static function getReservedQuantity(string $productId): int
    {
        return self::forProduct($productId)
            ->active()
            ->sum('quantity');
    }

    /**
     * Limpiar reservas expiradas
     */
    public static function cleanExpiredReservations(): int
    {
        $expiredReservations = self::expired()
            ->where('status', 'pending')
            ->get();

        $count = 0;
        foreach ($expiredReservations as $reservation) {
            $reservation->markAsExpired();
            $count++;
        }

        return $count;
    }

    /**
     * Cancelar reservas por sesión
     */
    public static function cancelSessionReservations(string $sessionId): int
    {
        return self::forSession($sessionId)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
    }

    /**
     * Cancelar reservas por usuario
     */
    public static function cancelUserReservations(string $userId): int
    {
        return self::forUser($userId)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
    }

    /**
     * Confirmar reservas por orden
     */
    public static function confirmOrderReservations(string $orderId): int
    {
        return self::forOrder($orderId)
            ->where('status', 'pending')
            ->update(['status' => 'confirmed']);
    }
}
