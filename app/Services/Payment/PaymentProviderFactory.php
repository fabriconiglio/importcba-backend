<?php

namespace App\Services\Payment;

use App\Models\PaymentMethod;
use InvalidArgumentException;

class PaymentProviderFactory
{
    private array $providers = [];

    public function __construct()
    {
        $this->registerProviders();
    }

    /**
     * Registrar proveedores disponibles
     */
    private function registerProviders(): void
    {
        // Mock provider siempre disponible
        $this->providers['mock'] = MockPaymentProvider::class;

        // Stripe provider (solo si está configurado)
        if (config('services.stripe.secret')) {
            $this->providers['stripe'] = StripePaymentProvider::class;
        }
    }

    /**
     * Obtener proveedor por nombre
     */
    public function getProvider(string $providerName): PaymentProviderInterface
    {
        if (!isset($this->providers[$providerName])) {
            throw new InvalidArgumentException("Proveedor de pago '{$providerName}' no encontrado");
        }

        $providerClass = $this->providers[$providerName];
        
        return new $providerClass();
    }

    /**
     * Obtener proveedor por método de pago
     */
    public function getProviderForPaymentMethod(string $paymentMethodId): PaymentProviderInterface
    {
        $paymentMethod = PaymentMethod::find($paymentMethodId);
        
        if (!$paymentMethod) {
            throw new InvalidArgumentException("Método de pago no encontrado");
        }

        // Mapear tipos de método a proveedores
        $providerMap = [
            'credit_card' => 'stripe',
            'debit_card' => 'stripe',
            'paypal' => 'mock', // Por ahora usamos mock para PayPal
            'bank_transfer' => 'mock',
            'cash_on_delivery' => 'mock',
            'mercadopago' => 'mock',
        ];

        $providerName = $providerMap[$paymentMethod->type] ?? 'mock';
        
        return $this->getProvider($providerName);
    }

    /**
     * Obtener proveedores disponibles
     */
    public function getAvailableProviders(): array
    {
        $available = [];
        
        foreach ($this->providers as $name => $class) {
            $provider = new $class();
            if ($provider->isAvailable()) {
                $available[$name] = [
                    'name' => $name,
                    'class' => $class,
                    'supported_methods' => $provider->getSupportedMethods(),
                ];
            }
        }

        return $available;
    }

    /**
     * Obtener proveedor por defecto
     */
    public function getDefaultProvider(): PaymentProviderInterface
    {
        // Priorizar Stripe si está disponible, sino usar Mock
        if (isset($this->providers['stripe'])) {
            $stripeProvider = new StripePaymentProvider();
            if ($stripeProvider->isAvailable()) {
                return $stripeProvider;
            }
        }

        return new MockPaymentProvider();
    }
} 