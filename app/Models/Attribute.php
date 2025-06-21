<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Attribute extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'is_required',
    ];  

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = Str::slug($model->name);
            }

            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
    

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function attributeValues()
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function productAttributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);  
    }

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }
    
}
