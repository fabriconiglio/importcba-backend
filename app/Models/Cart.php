<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Cart extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'session_id',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Indica que la clave primaria no es autoincremental
     */
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con los items del carrito
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Obtener el total del carrito
     */
    public function getTotal(): float
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->price;
        });
    }

    /**
     * Obtener el total de items en el carrito
     */
    public function getTotalItems(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Obtener el total de ahorro (diferencia entre precio original y precio de venta)
     */
    public function getTotalSavings(): float
    {
        return $this->items->sum(function ($item) {
            $originalPrice = $item->original_price ?? $item->price;
            return ($originalPrice - $item->price) * $item->quantity;
        });
    }

    /**
     * Limpiar el carrito (eliminar todos los items)
     */
    public function clear(): void
    {
        $this->items()->delete();
    }

    /**
     * Verificar si el carrito está vacío
     */
    public function isEmpty(): bool
    {
        return $this->items()->count() === 0;
    }

    /**
     * Verificar si el carrito ha expirado
     */
    public function hasExpired(): bool
    {
        return $this->expires_at && now()->greaterThan($this->expires_at);
    }

    /**
     * Extender la expiración del carrito
     */
    public function extendExpiration(int $minutes = 60 * 24 * 7): void
    {
        $this->expires_at = now()->addMinutes($minutes);
        $this->save();
    }

    /**
     * Convertir el carrito a orden
     */
    public function toOrder(): ?Order
    {
        if ($this->isEmpty()) {
            return null;
        }

        $order = Order::create([
            'user_id' => $this->user_id,
            'total' => $this->getTotal(),
            'status' => 'pending',
        ]);

        foreach ($this->items as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'original_price' => $item->original_price,
            ]);
        }

        $this->clear();
        return $order;
    }
}
