<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CartItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'price',
        'original_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
    ];

    /**
     * Indica que la clave primaria no es autoincremental
     */
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Relación con el carrito
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Relación con el producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Obtener el subtotal del item
     */
    public function getSubtotal(): float
    {
        return $this->quantity * $this->price;
    }

    /**
     * Obtener el ahorro del item
     */
    public function getSavings(): float
    {
        $originalPrice = $this->original_price ?? $this->price;
        return ($originalPrice - $this->price) * $this->quantity;
    }

    /**
     * Verificar si el item tiene descuento
     */
    public function hasDiscount(): bool
    {
        return $this->original_price && $this->original_price > $this->price;
    }

    /**
     * Obtener el porcentaje de descuento
     */
    public function getDiscountPercentage(): ?float
    {
        if (!$this->hasDiscount()) {
            return null;
        }

        return round((($this->original_price - $this->price) / $this->original_price) * 100, 2);
    }

    /**
     * Actualizar cantidad
     */
    public function updateQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            $this->delete();
            return;
        }

        $this->quantity = $quantity;
        $this->save();
    }

    /**
     * Actualizar precio desde el producto
     */
    public function updatePriceFromProduct(): void
    {
        $product = $this->product;
        if (!$product) {
            return;
        }

        $this->price = $product->getEffectivePrice();
        $this->original_price = $product->sale_price ? $product->price : null;
        $this->save();
    }
}