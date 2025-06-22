<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'cost',
        'estimated_days',
        'is_active',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
