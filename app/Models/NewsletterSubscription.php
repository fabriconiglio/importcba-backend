<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class NewsletterSubscription extends Model
{
    protected $fillable = [
        'email',
        'is_active',
        'subscribed_at',
        'unsubscribed_at',
        'subscription_source'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($subscription) {
            if (!$subscription->subscribed_at) {
                $subscription->subscribed_at = Carbon::now();
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function unsubscribe()
    {
        $this->update([
            'is_active' => false,
            'unsubscribed_at' => Carbon::now()
        ]);
    }

    public function resubscribe()
    {
        $this->update([
            'is_active' => true,
            'unsubscribed_at' => null,
            'subscribed_at' => Carbon::now()
        ]);
    }
}
