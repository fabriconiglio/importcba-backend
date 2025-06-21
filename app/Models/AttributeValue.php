<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeValue extends Model
{
    use HasUuids;

    protected $fillable = [
        'attribute_id',
        'value',
        'color_code',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
