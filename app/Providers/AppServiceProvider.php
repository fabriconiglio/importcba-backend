<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ProductImage;
use App\Observers\ProductImageObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ProductImage::observe(ProductImageObserver::class);
    }
}
