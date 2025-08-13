<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Payment\PaymentProviderFactory;
use App\Services\Payment\PaymentProviderInterface;
use App\Services\Payment\MockPaymentProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar el factory de proveedores de pago
        $this->app->singleton(PaymentProviderFactory::class, function ($app) {
            return new PaymentProviderFactory();
        });

        // Registrar el proveedor mock como default
        $this->app->bind(PaymentProviderInterface::class, function ($app) {
            return new MockPaymentProvider();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
