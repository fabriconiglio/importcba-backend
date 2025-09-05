<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ProductImage;
use App\Models\Category;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Order;
use App\Observers\ProductImageObserver;
use App\Observers\CategoryObserver;
use App\Observers\BannerObserver;
use App\Observers\BrandObserver;
use App\Observers\OrderObserver;

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
        Category::observe(CategoryObserver::class);
        Banner::observe(BannerObserver::class);
        Brand::observe(BrandObserver::class);
        Order::observe(OrderObserver::class);
    }
}
