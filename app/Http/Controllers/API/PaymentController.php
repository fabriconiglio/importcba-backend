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

/**
 * @OA\Tag(
 *     name="Payments",
 *     description="Endpoints para gestión de pagos y métodos de pago"
 * )
 */
class PaymentController extends Controller
{
    private PaymentProviderFactory $paymentFactory;

    public function __construct(PaymentProviderFactory $paymentFactory)
    {
        $this->paymentFactory = $paymentFactory;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payments/process",
     *     summary="Procesar pago",
     *     description="Procesa un pago para un pedido específico usando el método de pago seleccionado",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id","payment_method_id","amount","payment_data"},
     *             @OA\Property(property="order_id", type="string", format="uuid", description="ID del pedido a pagar"),
     *             @OA\Property(property="payment_method_id", type="string", format="uuid", description="ID del método de pago"),
     *             @OA\Property(property="amount", type="number", format="float", example=2999.97, description="Monto a pagar"),
     *             @OA\Property(property="currency", type="string", example="USD", description="Moneda del pago (opcional, por defecto USD)"),
     *             @OA\Property(
     *                 property="payment_data",
     *                 type="object",
     *                 description="Datos específicos del método de pago",
     *                 @OA\Property(property="card_token", type="string", example="tok_visa", description="Token de tarjeta (para pagos con tarjeta)"),
     *                 @OA\Property(property="payment_method_id", type="string", example="pm_1234567890", description="ID del método de pago (para pagos guardados)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Pago procesado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Pago procesado correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="payment_id", type="string", example="pi_1234567890"),
     *                 @OA\Property(property="transaction_id", type="string", example="txn_1234567890"),
     *                 @OA\Property(property="order_id", type="string", format="uuid"),
     *                 @OA\Property(property="amount", type="number", format="float", example=2999.97),
     *                 @OA\Property(property="currency", type="string", example="USD"),
     *                 @OA\Property(property="status", type="string", example="paid"),
     *                 @OA\Property(property="provider_data", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en el procesamiento del pago",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="El pedido ya ha sido pagado"),
     *             @OA\Property(property="error_code", type="string", example="PAYMENT_ALREADY_PAID"),
     *             @OA\Property(property="error_message", type="string", example="El pedido ya ha sido pagado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pedido no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/payments/info/{paymentId}",
     *     summary="Obtener información de pago",
     *     description="Obtiene información detallada de un pago específico",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="paymentId",
     *         in="path",
     *         description="ID del pago",
     *         required=true,
     *         @OA\Schema(type="string", example="pi_1234567890")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información de pago obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Información de pago obtenida correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="payment_id", type="string", example="pi_1234567890"),
     *                 @OA\Property(property="transaction_id", type="string", example="txn_1234567890"),
     *                 @OA\Property(property="order_id", type="string", format="uuid"),
     *                 @OA\Property(property="provider_data", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pago o pedido no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/payments/refund/{paymentId}",
     *     summary="Reembolsar pago",
     *     description="Procesa un reembolso para un pago específico",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="paymentId",
     *         in="path",
     *         description="ID del pago a reembolsar",
     *         required=true,
     *         @OA\Schema(type="string", example="pi_1234567890")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="amount", type="number", format="float", example=2999.97, description="Monto a reembolsar (opcional, por defecto monto completo)"),
     *             @OA\Property(property="reason", type="string", example="Cliente solicitó reembolso", description="Razón del reembolso (opcional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reembolso procesado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reembolso procesado correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="payment_id", type="string", example="pi_1234567890"),
     *                 @OA\Property(property="refund_id", type="string", example="re_1234567890"),
     *                 @OA\Property(property="order_id", type="string", format="uuid"),
     *                 @OA\Property(property="refund_amount", type="number", format="float", example=2999.97),
     *                 @OA\Property(property="provider_data", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en el procesamiento del reembolso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No se pudo procesar el reembolso"),
     *             @OA\Property(property="error_code", type="string", example="REFUND_FAILED"),
     *             @OA\Property(property="error_message", type="string", example="El pago no puede ser reembolsado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pago o pedido no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/payments/create-method",
     *     summary="Crear método de pago",
     *     description="Crea un nuevo método de pago (tarjeta de crédito/débito) para el usuario",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payment_method_type","card_number","expiry_month","expiry_year","cvv"},
     *             @OA\Property(property="payment_method_type", type="string", enum={"credit_card", "debit_card"}, example="credit_card", description="Tipo de método de pago"),
     *             @OA\Property(property="card_number", type="string", example="4242424242424242", description="Número de tarjeta"),
     *             @OA\Property(property="expiry_month", type="integer", example=12, description="Mes de expiración (1-12)"),
     *             @OA\Property(property="expiry_year", type="integer", example=2025, description="Año de expiración"),
     *             @OA\Property(property="cvv", type="string", example="123", description="Código de seguridad"),
     *             @OA\Property(property="cardholder_name", type="string", example="Juan Pérez", description="Nombre del titular (opcional)"),
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com", description="Email del titular (opcional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Método de pago creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Método de pago creado correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="payment_method_id", type="string", example="pm_1234567890"),
     *                 @OA\Property(property="type", type="string", example="credit_card"),
     *                 @OA\Property(
     *                     property="card_info",
     *                     type="object",
     *                     @OA\Property(property="brand", type="string", example="visa"),
     *                     @OA\Property(property="last4", type="string", example="4242"),
     *                     @OA\Property(property="exp_month", type="integer", example=12),
     *                     @OA\Property(property="exp_year", type="integer", example=2025)
     *                 ),
     *                 @OA\Property(property="provider_data", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al crear método de pago",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tarjeta inválida"),
     *             @OA\Property(property="error_code", type="string", example="INVALID_CARD"),
     *             @OA\Property(property="error_message", type="string", example="El número de tarjeta es inválido")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/payments/providers",
     *     summary="Obtener proveedores de pago",
     *     description="Obtiene la lista de proveedores de pago disponibles en el sistema",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Proveedores obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Proveedores de pago obtenidos correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="stripe"),
     *                     @OA\Property(property="display_name", type="string", example="Stripe"),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="supported_methods", type="array", @OA\Items(type="string", example="credit_card"))
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/payments/validate",
     *     summary="Validar datos de pago",
     *     description="Valida los datos de pago antes de procesar el pago",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payment_method_id","payment_data"},
     *             @OA\Property(property="payment_method_id", type="string", format="uuid", description="ID del método de pago"),
     *             @OA\Property(
     *                 property="payment_data",
     *                 type="object",
     *                 description="Datos de pago a validar",
     *                 @OA\Property(property="card_token", type="string", example="tok_visa", description="Token de tarjeta"),
     *                 @OA\Property(property="payment_method_id", type="string", example="pm_1234567890", description="ID del método de pago guardado")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Datos de pago válidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Datos de pago válidos")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos de pago inválidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Datos de pago inválidos"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="card_token", type="array", @OA\Items(type="string", example="Token de tarjeta inválido"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/payments/providers/{providerName}/methods",
     *     summary="Obtener métodos de pago por proveedor",
     *     description="Obtiene los métodos de pago soportados por un proveedor específico",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="providerName",
     *         in="path",
     *         description="Nombre del proveedor de pago",
     *         required=true,
     *         @OA\Schema(type="string", example="stripe")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Métodos de pago obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Métodos de pago obtenidos correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="provider", type="string", example="stripe"),
     *                 @OA\Property(
     *                     property="methods",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="type", type="string", example="credit_card"),
     *                         @OA\Property(property="name", type="string", example="Tarjeta de Crédito"),
     *                         @OA\Property(property="description", type="string", example="Pago con tarjeta de crédito"),
     *                         @OA\Property(property="is_active", type="boolean", example=true)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
