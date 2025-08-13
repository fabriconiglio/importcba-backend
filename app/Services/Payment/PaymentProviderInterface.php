<?php

namespace App\Services\Payment;

interface PaymentProviderInterface
{
    /**
     * Procesar un pago
     */
    public function processPayment(array $paymentData): PaymentResult;

    /**
     * Obtener información del pago
     */
    public function getPaymentInfo(string $paymentId): PaymentResult;

    /**
     * Reembolsar un pago
     */
    public function refundPayment(string $paymentId, float $amount = null): PaymentResult;

    /**
     * Verificar si el proveedor está disponible
     */
    public function isAvailable(): bool;

    /**
     * Obtener métodos de pago soportados
     */
    public function getSupportedMethods(): array;

    /**
     * Validar datos de pago
     */
    public function validatePaymentData(array $paymentData): array;

    /**
     * Crear método de pago (opcional, solo para algunos proveedores)
     */
    public function createPaymentMethod(array $cardData): PaymentResult;
} 