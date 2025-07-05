<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasUuids; 

    protected $fillable = [
        'name',
        'slug',
        'logo_url',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // CRÍTICO: Configuración UUID
    public $incrementing = false;
    protected $keyType = 'string';

    // Opcional: Si quieres mantener tu lógica personalizada
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
        });

        static::updating(function ($model) {
            // Generar slug automáticamente si no existe o si cambia el nombre
            if ((empty($model->slug) && !empty($model->name)) || $model->isDirty('name')) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    // Relaciones
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // Scopes útiles
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }
}