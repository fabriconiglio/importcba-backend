<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SiteSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    protected $casts = [
        'id' => 'string',
        'value' => 'array', // para soportar json, se maneja en el resource
    ];
}
