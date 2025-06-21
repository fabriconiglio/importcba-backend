<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'type',
        'is_active',
        'configuration',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'configuration' => 'array',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
    

    public function getPaymentMethods()
    {
        return PaymentMethod::all();
    }

    public function getPaymentMethod($id)
    {
        return PaymentMethod::find($id);
    }

    public function createPaymentMethod($data)
    {
        return PaymentMethod::create($data);
    }

    public function updatePaymentMethod($id, $data)
    {
        return PaymentMethod::find($id)->update($data);
    }

    public function deletePaymentMethod($id)
    {
        return PaymentMethod::find($id)->delete();
    }
}
