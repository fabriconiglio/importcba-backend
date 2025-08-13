<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentProviderFactory;
use App\Services\Payment\PaymentProviderInterface;
use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PaymentController extends Controller
{
    private PaymentProviderFactory $paymentFactory;

    public function __construct(PaymentProviderFactory $paymentFactory)
    {
        $this->paymentFactory = $paymentFactory;
    }

    /**
     * Procesar pago
     */
    public function processPayment(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|string|exists:orders,id',
                'payment_method_id' => 'required|string|exists:payment_methods,id',
                'amount' => 'required|numeric|min:0.01',
                'currency' => 'nullable|string|size:3',
                'payment_data' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $order = Order::where('id', $request->order_id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Verificar que el pedido no esté ya pagado
            if ($order->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido ya ha sido pagado'
                ], 400);
            }

            // Obtener proveedor de pago
            $provider = $this->paymentFactory->getProviderForPaymentMethod($request->payment_method_id);

            // Preparar datos de pago
            $paymentData = array_merge($request->payment_data, [
                'amount' => $request->amount,
                'currency' => $request->currency ?? 'USD',
                'order_id' => $order->id,
                'customer_id' => $user->id,
            ]);

            // Procesar pago
            $result = $provider->processPayment($paymentData);

            if ($result->success) {
                // Actualizar estado del pedido
                $order->update([
                    'payment_status' => 'paid',
                    'payment_method' => $request->payment_method_id,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'payment_id' => $result->paymentId,
                        'transaction_id' => $result->transactionId,
                        'order_id' => $order->id,
                        'amount' => $request->amount,
                        'currency' => $request->currency ?? 'USD',
                        'status' => 'paid',
                        'provider_data' => $result->data,
                    ],
                    'message' => $result->message
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result->message,
                    'error_code' => $result->errorCode,
                    'error_message' => $result->errorMessage,
                ], 400);
            }

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información de pago
     */
    public function getPaymentInfo(Request $request, string $paymentId): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Buscar el pedido asociado al pago
            $order = Order::where('user_id', $user->id)
                ->where('payment_status', 'paid')
                ->firstOrFail();

            // Obtener proveedor de pago
            $provider = $this->paymentFactory->getDefaultProvider();

            // Obtener información del pago
            $result = $provider->getPaymentInfo($paymentId);

            if ($result->success) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'payment_id' => $result->paymentId,
                        'transaction_id' => $result->transactionId,
                        'order_id' => $order->id,
                        'provider_data' => $result->data,
                    ],
                    'message' => $result->message
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result->message,
                    'error_code' => $result->errorCode,
                    'error_message' => $result->errorMessage,
                ], 404);
            }

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reembolsar pago
     */
    public function refundPayment(Request $request, string $paymentId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'nullable|numeric|min:0.01',
                'reason' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            
            // Buscar el pedido asociado al pago
            $order = Order::where('user_id', $user->id)
                ->where('payment_status', 'paid')
                ->firstOrFail();

            // Obtener proveedor de pago
            $provider = $this->paymentFactory->getDefaultProvider();

            // Procesar reembolso
            $result = $provider->refundPayment($paymentId, $request->amount);

            if ($result->success) {
                // Actualizar estado del pedido
                $order->update([
                    'payment_status' => 'refunded',
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'payment_id' => $result->paymentId,
                        'refund_id' => $result->transactionId,
                        'order_id' => $order->id,
                        'refund_amount' => $request->amount ?? $order->total_amount,
                        'provider_data' => $result->data,
                    ],
                    'message' => $result->message
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result->message,
                    'error_code' => $result->errorCode,
                    'error_message' => $result->errorMessage,
                ], 400);
            }

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar reembolso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear método de pago (para tarjetas)
     */
    public function createPaymentMethod(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_method_type' => 'required|string|in:credit_card,debit_card',
                'card_number' => 'required|string|min:13|max:19',
                'expiry_month' => 'required|integer|between:1,12',
                'expiry_year' => 'required|integer|min:' . date('Y'),
                'cvv' => 'required|string|min:3|max:4',
                'cardholder_name' => 'nullable|string|max:255',
                'email' => 'nullable|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            // Obtener proveedor de pago
            $provider = $this->paymentFactory->getProvider('stripe');

            // Preparar datos de tarjeta
            $cardData = [
                'card_number' => $request->card_number,
                'expiry_month' => $request->expiry_month,
                'expiry_year' => $request->expiry_year,
                'cvv' => $request->cvv,
                'cardholder_name' => $request->cardholder_name,
                'email' => $request->email,
            ];

            // Crear método de pago
            $result = $provider->createPaymentMethod($cardData);

            if ($result->success) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'payment_method_id' => $result->paymentId,
                        'type' => $request->payment_method_type,
                        'card_info' => $result->data['card'] ?? null,
                        'provider_data' => $result->data,
                    ],
                    'message' => $result->message
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result->message,
                    'error_code' => $result->errorCode,
                    'error_message' => $result->errorMessage,
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear método de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener proveedores disponibles
     */
    public function getProviders(): JsonResponse
    {
        try {
            $providers = $this->paymentFactory->getAvailableProviders();

            return response()->json([
                'success' => true,
                'data' => $providers,
                'message' => 'Proveedores de pago obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener proveedores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar datos de pago
     */
    public function validatePaymentData(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_method_id' => 'required|string|exists:payment_methods,id',
                'payment_data' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Obtener proveedor de pago
            $provider = $this->paymentFactory->getProviderForPaymentMethod($request->payment_method_id);

            // Validar datos de pago
            $validation = $provider->validatePaymentData($request->payment_data);

            if ($validation['valid']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Datos de pago válidos'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de pago inválidos',
                    'errors' => $validation['errors']
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar datos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener métodos de pago soportados por proveedor
     */
    public function getSupportedMethods(Request $request, string $providerName): JsonResponse
    {
        try {
            $provider = $this->paymentFactory->getProvider($providerName);
            $methods = $provider->getSupportedMethods();

            return response()->json([
                'success' => true,
                'data' => [
                    'provider' => $providerName,
                    'methods' => $methods,
                ],
                'message' => 'Métodos de pago obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener métodos: ' . $e->getMessage()
            ], 500);
        }
    }
}
