<?php

namespace App\Services\Payment;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Exception\ApiErrorException;

class StripePaymentProvider implements PaymentProviderInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'secret_key' => config('services.stripe.secret'),
            'currency' => 'usd',
            'api_version' => '2023-10-16',
        ], $config);

        // Configurar Stripe
        Stripe::setApiKey($this->config['secret_key']);
        Stripe::setApiVersion($this->config['api_version']);
    }

    public function processPayment(array $paymentData): PaymentResult
    {
        try {
            // Validar datos de pago
            $validation = $this->validatePaymentData($paymentData);
            if (!empty($validation['errors'])) {
                return PaymentResult::failure(
                    'Datos de pago inválidos',
                    'VALIDATION_ERROR',
                    implode(', ', $validation['errors'])
                );
            }

            // Crear PaymentIntent con Stripe
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) ($paymentData['amount'] * 100), // Stripe usa centavos
                'currency' => $paymentData['currency'] ?? $this->config['currency'],
                'payment_method' => $paymentData['payment_method_id'],
                'confirmation_method' => 'manual',
                'confirm' => true,
                'metadata' => [
                    'order_id' => $paymentData['order_id'] ?? null,
                    'customer_id' => $paymentData['customer_id'] ?? null,
                ],
            ]);

            if ($paymentIntent->status === 'succeeded') {
                return PaymentResult::success(
                    'Pago procesado exitosamente',
                    $paymentIntent->id,
                    $paymentIntent->latest_charge,
                    [
                        'amount' => $paymentData['amount'],
                        'currency' => $paymentIntent->currency,
                        'status' => $paymentIntent->status,
                        'created_at' => date('c', $paymentIntent->created),
                        'provider' => 'stripe',
                    ]
                );
            } else {
                return PaymentResult::failure(
                    'Pago requiere confirmación adicional',
                    'REQUIRES_ACTION',
                    'El pago requiere autenticación adicional'
                );
            }

        } catch (ApiErrorException $e) {
            return PaymentResult::failure(
                'Error en el procesamiento del pago',
                $e->getStripeCode(),
                $e->getMessage()
            );
        } catch (\Exception $e) {
            return PaymentResult::failure(
                'Error interno del servidor',
                'INTERNAL_ERROR',
                $e->getMessage()
            );
        }
    }

    public function getPaymentInfo(string $paymentId): PaymentResult
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentId);

            return PaymentResult::success(
                'Información de pago obtenida',
                $paymentIntent->id,
                $paymentIntent->latest_charge,
                [
                    'amount' => $paymentIntent->amount / 100,
                    'currency' => $paymentIntent->currency,
                    'status' => $paymentIntent->status,
                    'created_at' => date('c', $paymentIntent->created),
                    'provider' => 'stripe',
                ]
            );

        } catch (ApiErrorException $e) {
            return PaymentResult::failure(
                'Pago no encontrado',
                'PAYMENT_NOT_FOUND',
                $e->getMessage()
            );
        } catch (\Exception $e) {
            return PaymentResult::failure(
                'Error interno del servidor',
                'INTERNAL_ERROR',
                $e->getMessage()
            );
        }
    }

    public function refundPayment(string $paymentId, float $amount = null): PaymentResult
    {
        try {
            $refundData = [
                'payment_intent' => $paymentId,
            ];

            if ($amount !== null) {
                $refundData['amount'] = (int) ($amount * 100);
            }

            $refund = Refund::create($refundData);

            return PaymentResult::success(
                'Reembolso procesado exitosamente',
                $paymentId,
                $refund->id,
                [
                    'refund_amount' => $refund->amount / 100,
                    'refund_id' => $refund->id,
                    'status' => $refund->status,
                    'processed_at' => date('c', $refund->created),
                    'provider' => 'stripe',
                ]
            );

        } catch (ApiErrorException $e) {
            return PaymentResult::failure(
                'Error al procesar reembolso',
                $e->getStripeCode(),
                $e->getMessage()
            );
        } catch (\Exception $e) {
            return PaymentResult::failure(
                'Error interno del servidor',
                'INTERNAL_ERROR',
                $e->getMessage()
            );
        }
    }

    public function isAvailable(): bool
    {
        return !empty($this->config['secret_key']);
    }

    public function getSupportedMethods(): array
    {
        return [
            'credit_card' => [
                'name' => 'Tarjeta de Crédito',
                'processors' => ['visa', 'mastercard', 'amex', 'discover'],
                'requires_cvv' => true,
                'requires_expiry' => true,
                'supports_3ds' => true,
            ],
            'debit_card' => [
                'name' => 'Tarjeta de Débito',
                'processors' => ['visa', 'mastercard'],
                'requires_cvv' => true,
                'requires_expiry' => true,
                'supports_3ds' => true,
            ],
        ];
    }

    public function validatePaymentData(array $paymentData): array
    {
        $errors = [];

        // Validar campos requeridos
        $requiredFields = ['amount', 'payment_method_id'];
        foreach ($requiredFields as $field) {
            if (!isset($paymentData[$field]) || empty($paymentData[$field])) {
                $errors[] = "El campo '{$field}' es requerido";
            }
        }

        // Validar monto
        if (isset($paymentData['amount']) && (!is_numeric($paymentData['amount']) || $paymentData['amount'] <= 0)) {
            $errors[] = 'El monto debe ser un número positivo';
        }

        // Validar payment_method_id (debe ser un ID válido de Stripe)
        if (isset($paymentData['payment_method_id']) && !preg_match('/^pm_/', $paymentData['payment_method_id'])) {
            $errors[] = 'ID de método de pago inválido';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Crear PaymentMethod (para tarjetas)
     */
    public function createPaymentMethod(array $cardData): PaymentResult
    {
        try {
            $paymentMethod = \Stripe\PaymentMethod::create([
                'type' => 'card',
                'card' => [
                    'number' => $cardData['card_number'],
                    'exp_month' => $cardData['expiry_month'],
                    'exp_year' => $cardData['expiry_year'],
                    'cvc' => $cardData['cvv'],
                ],
                'billing_details' => [
                    'name' => $cardData['cardholder_name'] ?? null,
                    'email' => $cardData['email'] ?? null,
                ],
            ]);

            return PaymentResult::success(
                'Método de pago creado exitosamente',
                $paymentMethod->id,
                null,
                [
                    'payment_method_id' => $paymentMethod->id,
                    'type' => $paymentMethod->type,
                    'card' => [
                        'brand' => $paymentMethod->card->brand,
                        'last4' => $paymentMethod->card->last4,
                        'exp_month' => $paymentMethod->card->exp_month,
                        'exp_year' => $paymentMethod->card->exp_year,
                    ],
                    'provider' => 'stripe',
                ]
            );

        } catch (ApiErrorException $e) {
            return PaymentResult::failure(
                'Error al crear método de pago',
                $e->getStripeCode(),
                $e->getMessage()
            );
        } catch (\Exception $e) {
            return PaymentResult::failure(
                'Error interno del servidor',
                'INTERNAL_ERROR',
                $e->getMessage()
            );
        }
    }
} 