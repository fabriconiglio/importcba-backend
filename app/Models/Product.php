<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'description',
        'short_description',
        'category_id',
        'brand_id',
        'price',
        'sale_price',
        'cost_price',
        'stock_quantity',
        'min_stock_level',
        'weight',
        'dimensions',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'stock_quantity' => 'integer',
        'min_stock_level' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generar slug automáticamente si no existe
            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = Str::slug($model->name);
            }
            
            // El UUID ya se maneja con HasUuids trait, pero si quieres forzarlo:
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            // Generar SKU automáticamente si no existe
            if (empty($model->sku)) {
                $model->sku = self::generateUniqueSku($model->name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name')) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    // =============================================
    // MÉTODOS PARA GENERAR SKU
    // =============================================

    /**
     * Generar SKU único basado en el nombre del producto
     */
    public static function generateUniqueSku(string $name): string
    {
        // Crear base del SKU desde el nombre
        $baseSku = strtoupper(Str::slug($name, ''));
        
        // Limitar a 6 caracteres y agregar sufijo numérico
        $baseSku = substr($baseSku, 0, 6);
        
        // Agregar número aleatorio
        $randomNumber = str_pad(mt_rand(1, 99), 2, '0', STR_PAD_LEFT);
        $sku = $baseSku . $randomNumber;

        // Verificar que sea único
        $counter = 1;
        $originalSku = $sku;
        
        while (self::where('sku', $sku)->exists()) {
            $sku = $originalSku . str_pad($counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $sku;
    }

    // =============================================
    // RELACIONES
    // =============================================

    /**
     * Relación con categoría
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relación con marca
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Relación con imágenes del producto (ordenadas)
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Relación con imagen principal
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Relación con atributos del producto (tabla pivot)
     */
    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    /**
     * Relación many-to-many con atributos
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes');
    }

    // =============================================
    // SCOPES
    // =============================================

    /**
     * Scope para productos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para productos destacados
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope para productos con stock
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    /**
     * Scope para productos con stock bajo
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'min_stock_level');
    }

    /**
     * Scope para buscar por slug
     */
    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * Scope para buscar por nombre o SKU
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($query) use ($search) {
            $query->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('sku', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
        });
    }

    // =============================================
    // ACCESSORS Y MÉTODOS DE UTILIDAD
    // =============================================

    /**
     * Accessor para URL de imagen principal (para Filament)
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        $imageUrl = $this->primaryImage?->url
            ?? $this->images()->orderBy('sort_order')->value('url');
            
        if ($imageUrl) {
            // Si la URL ya es completa, la devolvemos tal como está
            if (str_starts_with($imageUrl, 'http')) {
                return $imageUrl;
            }
            // Si es una ruta relativa, la construimos con storage
            return $imageUrl;
        }
        
        // Retornar null para que el frontend use su propia imagen por defecto
        return null;
    }

    /**
     * Obtener todas las URLs de imágenes
     */
    public function getImageUrls(): array
    {
        return $this->images->pluck('url')->toArray();
    }

    /**
     * Obtener precio con descuento si aplica
     */
    public function getEffectivePrice(): float
    {
        return $this->sale_price ?? $this->price;
    }

    /**
     * Verificar si tiene descuento
     */
    public function hasDiscount(): bool
    {
        return !is_null($this->sale_price) && $this->sale_price < $this->price;
    }

    /**
     * Calcular porcentaje de descuento
     */
    public function getDiscountPercentage(): ?float
    {
        if (!$this->hasDiscount()) {
            return null;
        }

        return round((($this->price - $this->sale_price) / $this->price) * 100, 2);
    }

    /**
     * Verificar si está en stock
     */
    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * Verificar si tiene stock bajo
     */
    public function hasLowStock(): bool
    {
        return $this->stock_quantity <= $this->min_stock_level;
    }

    /**
     * Decrementar stock
     */
    public function decrementStock(int $quantity): bool
    {
        if ($this->stock_quantity >= $quantity) {
            $this->decrement('stock_quantity', $quantity);
            return true;
        }
        
        return false;
    }

    /**
     * Incrementar stock
     */
    public function incrementStock(int $quantity): void
    {
        $this->increment('stock_quantity', $quantity);
    }

    /**
     * Verificar si tiene imágenes
     */
    public function hasImages(): bool
    {
        return $this->images()->count() > 0;
    }

    /**
     * Obtener la primera imagen (principal o primera disponible)
     */
    public function getFirstImage(): ?ProductImage
    {
        return $this->primaryImage ?? $this->images->first();
    }
}