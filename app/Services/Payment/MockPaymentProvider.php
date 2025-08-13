<?php

namespace App\Services\Payment;

use Illuminate\Support\Str;

class MockPaymentProvider implements PaymentProviderInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'success_rate' => 0.95, // 95% de éxito
            'processing_delay' => 0, // Sin delay
            'simulate_failures' => false,
        ], $config);
    }

    public function processPayment(array $paymentData): PaymentResult
    {
        // Validar datos de pago
        $validation = $this->validatePaymentData($paymentData);
        if (!empty($validation['errors'])) {
            return PaymentResult::failure(
                'Datos de pago inválidos',
                'VALIDATION_ERROR',
                implode(', ', $validation['errors'])
            );
        }

        // Simular procesamiento
        if ($this->config['simulate_failures'] || rand(1, 100) > ($this->config['success_rate'] * 100)) {
            return PaymentResult::failure(
                'Pago rechazado por el proveedor',
                'PAYMENT_DECLINED',
                'Tarjeta rechazada o fondos insuficientes'
            );
        }

        // Simular delay de procesamiento
        if ($this->config['processing_delay'] > 0) {
            sleep($this->config['processing_delay']);
        }

        // Generar IDs únicos
        $paymentId = 'mock_pay_' . Str::random(16);
        $transactionId = 'mock_txn_' . Str::random(16);

        return PaymentResult::success(
            'Pago procesado exitosamente',
            $paymentId,
            $transactionId,
            [
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'] ?? 'USD',
                'payment_method' => $paymentData['payment_method'],
                'processed_at' => now()->toISOString(),
                'provider' => 'mock',
            ]
        );
    }

    public function getPaymentInfo(string $paymentId): PaymentResult
    {
        // Simular búsqueda de información de pago
        if (!Str::startsWith($paymentId, 'mock_pay_')) {
            return PaymentResult::failure(
                'Pago no encontrado',
                'PAYMENT_NOT_FOUND'
            );
        }

        return PaymentResult::success(
            'Información de pago obtenida',
            $paymentId,
            'mock_txn_' . Str::random(16),
            [
                'status' => 'completed',
                'amount' => 100.00,
                'currency' => 'USD',
                'created_at' => now()->subMinutes(5)->toISOString(),
                'processed_at' => now()->subMinutes(4)->toISOString(),
                'provider' => 'mock',
            ]
        );
    }

    public function refundPayment(string $paymentId, float $amount = null): PaymentResult
    {
        // Simular reembolso
        if (!Str::startsWith($paymentId, 'mock_pay_')) {
            return PaymentResult::failure(
                'Pago no encontrado',
                'PAYMENT_NOT_FOUND'
            );
        }

        $refundId = 'mock_refund_' . Str::random(16);

        return PaymentResult::success(
            'Reembolso procesado exitosamente',
            $paymentId,
            $refundId,
            [
                'refund_amount' => $amount,
                'refund_id' => $refundId,
                'processed_at' => now()->toISOString(),
                'provider' => 'mock',
            ]
        );
    }

    public function isAvailable(): bool
    {
        return true; // Mock siempre está disponible
    }

    public function getSupportedMethods(): array
    {
        return [
            'credit_card' => [
                'name' => 'Tarjeta de Crédito',
                'processors' => ['visa', 'mastercard', 'amex'],
                'requires_cvv' => true,
                'requires_expiry' => true,
            ],
            'debit_card' => [
                'name' => 'Tarjeta de Débito',
                'processors' => ['visa', 'mastercard'],
                'requires_cvv' => true,
                'requires_expiry' => true,
            ],
            'paypal' => [
                'name' => 'PayPal',
                'processors' => ['paypal'],
                'requires_cvv' => false,
                'requires_expiry' => false,
            ],
            'bank_transfer' => [
                'name' => 'Transferencia Bancaria',
                'processors' => ['ach', 'wire'],
                'requires_cvv' => false,
                'requires_expiry' => false,
            ],
            'cash_on_delivery' => [
                'name' => 'Efectivo contra Entrega',
                'processors' => ['cash'],
                'requires_cvv' => false,
                'requires_expiry' => false,
            ],
        ];
    }

    public function validatePaymentData(array $paymentData): array
    {
        $errors = [];

        // Validar campos requeridos
        $requiredFields = ['amount', 'payment_method'];
        foreach ($requiredFields as $field) {
            if (!isset($paymentData[$field]) || empty($paymentData[$field])) {
                $errors[] = "El campo '{$field}' es requerido";
            }
        }

        // Validar monto
        if (isset($paymentData['amount']) && (!is_numeric($paymentData['amount']) || $paymentData['amount'] <= 0)) {
            $errors[] = 'El monto debe ser un número positivo';
        }

        // Validar método de pago
        $supportedMethods = array_keys($this->getSupportedMethods());
        if (isset($paymentData['payment_method']) && !in_array($paymentData['payment_method'], $supportedMethods)) {
            $errors[] = 'Método de pago no soportado';
        }

        // Validaciones específicas por método
        if (isset($paymentData['payment_method'])) {
            switch ($paymentData['payment_method']) {
                case 'credit_card':
                case 'debit_card':
                    if (!isset($paymentData['card_number']) || strlen($paymentData['card_number']) < 13) {
                        $errors[] = 'Número de tarjeta inválido';
                    }
                    if (!isset($paymentData['cvv']) || strlen($paymentData['cvv']) < 3) {
                        $errors[] = 'CVV inválido';
                    }
                    if (!isset($paymentData['expiry_month']) || !isset($paymentData['expiry_year'])) {
                        $errors[] = 'Fecha de expiración requerida';
                    }
                    break;

                case 'paypal':
                    if (!isset($paymentData['paypal_email']) || !filter_var($paymentData['paypal_email'], FILTER_VALIDATE_EMAIL)) {
                        $errors[] = 'Email de PayPal inválido';
                    }
                    break;

                case 'bank_transfer':
                    if (!isset($paymentData['account_number']) || strlen($paymentData['account_number']) < 8) {
                        $errors[] = 'Número de cuenta inválido';
                    }
                    if (!isset($paymentData['routing_number']) || strlen($paymentData['routing_number']) < 8) {
                        $errors[] = 'Número de routing inválido';
                    }
                    break;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Crear método de pago (simulado)
     */
    public function createPaymentMethod(array $cardData): PaymentResult
    {
        // Validar datos de tarjeta
        $errors = [];
        
        if (!isset($cardData['card_number']) || strlen($cardData['card_number']) < 13) {
            $errors[] = 'Número de tarjeta inválido';
        }
        
        if (!isset($cardData['expiry_month']) || !isset($cardData['expiry_year'])) {
            $errors[] = 'Fecha de expiración requerida';
        }
        
        if (!isset($cardData['cvv']) || strlen($cardData['cvv']) < 3) {
            $errors[] = 'CVV inválido';
        }

        if (!empty($errors)) {
            return PaymentResult::failure(
                'Datos de tarjeta inválidos',
                'VALIDATION_ERROR',
                implode(', ', $errors)
            );
        }

        // Simular creación de método de pago
        $paymentMethodId = 'mock_pm_' . Str::random(16);

        return PaymentResult::success(
            'Método de pago creado exitosamente',
            $paymentMethodId,
            null,
            [
                'payment_method_id' => $paymentMethodId,
                'type' => 'card',
                'card' => [
                    'brand' => 'visa',
                    'last4' => substr($cardData['card_number'], -4),
                    'exp_month' => $cardData['expiry_month'],
                    'exp_year' => $cardData['expiry_year'],
                ],
                'provider' => 'mock',
            ]
        );
    }
} 