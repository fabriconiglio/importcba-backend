<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Address;
use App\Models\ShippingMethod;
use App\Models\PaymentMethod;
use App\Models\Coupon;
use App\Services\StockReservationService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @OA\Tag(
 *     name="Checkout",
 *     description="Endpoints para proceso de checkout y creación de pedidos"
 * )
 */
class CheckoutController extends Controller
{
    private StockReservationService $stockReservationService;
    private EmailService $emailService;

    public function __construct(StockReservationService $stockReservationService, EmailService $emailService)
    {
        $this->stockReservationService = $stockReservationService;
        $this->emailService = $emailService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/checkout/init",
     *     summary="Iniciar checkout",
     *     description="Obtiene información inicial para el proceso de checkout",
     *     tags={"Checkout"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Información de checkout obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Información de checkout obtenida"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="cart",
     *                     type="object",
     *                     @OA\Property(property="total_items", type="integer", example=3),
     *                     @OA\Property(property="subtotal", type="number", format="float", example=2999.97),
     *                     @OA\Property(property="total", type="number", format="float", example=2999.97)
     *                 ),
     *                 @OA\Property(
     *                     property="shipping_methods",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="name", type="string", example="Envío Estándar"),
     *                         @OA\Property(property="description", type="string", example="Entrega en 3-5 días hábiles"),
     *                         @OA\Property(property="cost", type="number", format="float", example=500.00),
     *                         @OA\Property(property="estimated_days", type="integer", example=5)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="payment_methods",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="name", type="string", example="Tarjeta de Crédito"),
     *                         @OA\Property(property="type", type="string", example="credit_card"),
     *                         @OA\Property(property="description", type="string", example="Pago con tarjeta de crédito")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="user_addresses",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Address")
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
     *         response=422,
     *         description="Carrito vacío",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function initiate(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Obtener carrito del usuario
            $cart = Cart::where('user_id', $user->id)->with(['items.product'])->first();
            
            Log::info('Checkout initiate debug', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'cart_id' => $cart ? $cart->id : null,
                'cart_items_count' => $cart ? $cart->items->count() : 0,
                'cart_total' => $cart ? $cart->getTotal() : 0
            ]);
            
            if (!$cart || $cart->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El carrito está vacío'
                ], 400);
            }

            // Verificar stock disponible considerando reservas
            $stockErrors = [];
            foreach ($cart->items as $item) {
                $availableStock = $this->stockReservationService->getAvailableStock($item->product->id);
                if ($availableStock < $item->quantity) {
                    $stockErrors[] = "Producto '{$item->product->name}' - Stock insuficiente (disponible: {$availableStock}, solicitado: {$item->quantity})";
                }
            }

            if (!empty($stockErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de stock',
                    'errors' => $stockErrors
                ], 400);
            }

            // Obtener direcciones del usuario
            $addresses = Address::where('user_id', $user->id)->get();

            // Obtener métodos de envío activos
            $shippingMethods = ShippingMethod::where('is_active', true)->get();

            // Obtener métodos de pago activos
            $paymentMethods = PaymentMethod::where('is_active', true)->get();

            // Calcular totales
            $subtotal = $cart->getTotal();
            $shippingCost = 0;
            $taxAmount = $this->calculateTax($subtotal);
            $discountAmount = 0;
            $totalAmount = $subtotal + $shippingCost + $taxAmount - $discountAmount;

            return response()->json([
                'success' => true,
                'data' => [
                    'cart_summary' => [
                        'total_items' => $cart->getTotalItems(),
                        'subtotal' => $subtotal,
                        'shipping_cost' => $shippingCost,
                        'tax_amount' => $taxAmount,
                        'discount_amount' => $discountAmount,
                        'total_amount' => $totalAmount,
                    ],
                    'addresses' => $addresses,
                    'shipping_methods' => $shippingMethods,
                    'payment_methods' => $paymentMethods,
                    'items' => $cart->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product' => [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'slug' => $item->product->slug,
                                'image' => $item->product->primary_image_url,
                                'stock_quantity' => $item->product->stock_quantity,
                            ],
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'original_price' => $item->original_price,
                            'subtotal' => $item->getSubtotal(),
                            'has_discount' => $item->hasDiscount(),
                            'discount_percentage' => $item->getDiscountPercentage(),
                        ];
                    }),
                ],
                'message' => 'Checkout iniciado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar checkout: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/checkout/calculate",
     *     summary="Calcular totales de checkout",
     *     description="Calcula los totales del pedido incluyendo envío, impuestos y descuentos",
     *     tags={"Checkout"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"shipping_method_id","shipping_address"},
     *             @OA\Property(property="shipping_method_id", type="string", format="uuid", description="ID del método de envío"),
     *             @OA\Property(property="payment_method_id", type="string", format="uuid", description="ID del método de pago"),
     *             @OA\Property(property="coupon_code", type="string", example="DESCUENTO20", description="Código de cupón (opcional)"),
     *             @OA\Property(
     *                 property="shipping_address",
     *                 type="object",
     *                 required={"first_name","last_name","address","city","state","postal_code","country","phone"},
     *                 @OA\Property(property="first_name", type="string", example="Juan"),
     *                 @OA\Property(property="last_name", type="string", example="Pérez"),
     *                 @OA\Property(property="address", type="string", example="Av. Corrientes 1234"),
     *                 @OA\Property(property="city", type="string", example="Buenos Aires"),
     *                 @OA\Property(property="state", type="string", example="Buenos Aires"),
     *                 @OA\Property(property="postal_code", type="string", example="1043"),
     *                 @OA\Property(property="country", type="string", example="Argentina"),
     *                 @OA\Property(property="phone", type="string", example="+5491112345678")
     *             ),
     *             @OA\Property(
     *                 property="billing_address",
     *                 type="object",
     *                 description="Dirección de facturación (opcional)"
     *             ),
     *             @OA\Property(property="notes", type="string", example="Entregar después de las 18:00", description="Notas adicionales")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Totales calculados exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Totales calculados correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="subtotal", type="number", format="float", example=2999.97),
     *                 @OA\Property(property="shipping_cost", type="number", format="float", example=500.00),
     *                 @OA\Property(property="tax_amount", type="number", format="float", example=599.99),
     *                 @OA\Property(property="discount_amount", type="number", format="float", example=600.00),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=3499.96),
     *                 @OA\Property(
     *                     property="coupon_applied",
     *                     type="object",
     *                     @OA\Property(property="code", type="string", example="DESCUENTO20"),
     *                     @OA\Property(property="discount_type", type="string", example="percentage"),
     *                     @OA\Property(property="discount_value", type="number", format="float", example=20.0)
     *                 ),
     *                 @OA\Property(
     *                     property="shipping_method",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="Envío Estándar"),
     *                     @OA\Property(property="estimated_days", type="integer", example=5)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function calculate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'shipping_method_id' => 'required|string|exists:shipping_methods,id',
                'shipping_address' => 'required|array',
                'shipping_address.street_address' => 'required|string',
                'shipping_address.city' => 'required|string',
                'shipping_address.state' => 'required|string',
                'shipping_address.postal_code' => 'required|string',
                'shipping_address.country' => 'required|string',
                'billing_address' => 'nullable|array',
                'billing_address.street_address' => 'required_with:billing_address|string',
                'billing_address.city' => 'required_with:billing_address|string',
                'billing_address.state' => 'required_with:billing_address|string',
                'billing_address.postal_code' => 'required_with:billing_address|string',
                'billing_address.country' => 'required_with:billing_address|string',
                'coupon_code' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $cart = Cart::where('user_id', $user->id)->with(['items.product'])->first();

            if (!$cart || $cart->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El carrito está vacío'
                ], 400);
            }

            // Obtener método de envío
            $shippingMethod = ShippingMethod::findOrFail($request->shipping_method_id);
            $shippingCost = $shippingMethod->cost;

            // Calcular subtotal
            $subtotal = $cart->getTotal();

            // Calcular impuestos
            $taxAmount = $this->calculateTax($subtotal);

            // Aplicar cupón si existe
            $discountAmount = 0;
            $coupon = null;
            if ($request->filled('coupon_code')) {
                $coupon = Coupon::where('code', $request->coupon_code)
                    ->where('is_active', true)
                    ->where('expires_at', '>', now())
                    ->first();

                if ($coupon) {
                    // Verificar si el usuario ya usó este cupón
                    $usageCount = $coupon->usages()->where('user_id', $user->id)->count();
                    if ($usageCount >= $coupon->max_uses_per_user) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Ya has usado este cupón el máximo de veces permitido'
                        ], 400);
                    }

                    // Verificar monto mínimo
                    if ($subtotal < $coupon->minimum_amount) {
                        return response()->json([
                            'success' => false,
                            'message' => "El monto mínimo para usar este cupón es $" . number_format($coupon->minimum_amount, 2)
                        ], 400);
                    }

                    // Calcular descuento
                    if ($coupon->discount_type === 'percentage') {
                        $discountAmount = ($subtotal * $coupon->discount_value) / 100;
                    } else {
                        $discountAmount = $coupon->discount_value;
                    }

                    // No permitir descuento mayor al subtotal
                    $discountAmount = min($discountAmount, $subtotal);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cupón no válido o expirado'
                    ], 400);
                }
            }

            // Calcular total
            $totalAmount = $subtotal + $shippingCost + $taxAmount - $discountAmount;

            return response()->json([
                'success' => true,
                'data' => [
                    'subtotal' => $subtotal,
                    'shipping_cost' => $shippingCost,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'shipping_method' => [
                        'id' => $shippingMethod->id,
                        'name' => $shippingMethod->name,
                        'description' => $shippingMethod->description,
                        'cost' => $shippingMethod->cost,
                        'estimated_days' => $shippingMethod->estimated_days,
                    ],
                    'coupon' => $coupon ? [
                        'id' => $coupon->id,
                        'code' => $coupon->code,
                        'discount_type' => $coupon->discount_type,
                        'discount_value' => $coupon->discount_value,
                        'discount_amount' => $discountAmount,
                    ] : null,
                    'breakdown' => [
                        'items_total' => $subtotal,
                        'shipping' => $shippingCost,
                        'tax' => $taxAmount,
                        'discount' => -$discountAmount,
                        'total' => $totalAmount,
                    ]
                ],
                'message' => 'Cálculo realizado correctamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Método de envío no encontrado'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular totales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/checkout/confirm",
     *     summary="Confirmar pedido",
     *     description="Confirma el pedido y crea la orden en el sistema",
     *     tags={"Checkout"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"shipping_method_id","payment_method_id","shipping_address"},
     *             @OA\Property(property="shipping_method_id", type="string", format="uuid", description="ID del método de envío"),
     *             @OA\Property(property="payment_method_id", type="string", format="uuid", description="ID del método de pago"),
     *             @OA\Property(property="coupon_code", type="string", example="DESCUENTO20", description="Código de cupón (opcional)"),
     *             @OA\Property(
     *                 property="shipping_address",
     *                 type="object",
     *                 required={"first_name","last_name","address","city","state","postal_code","country","phone"},
     *                 @OA\Property(property="first_name", type="string", example="Juan"),
     *                 @OA\Property(property="last_name", type="string", example="Pérez"),
     *                 @OA\Property(property="address", type="string", example="Av. Corrientes 1234"),
     *                 @OA\Property(property="city", type="string", example="Buenos Aires"),
     *                 @OA\Property(property="state", type="string", example="Buenos Aires"),
     *                 @OA\Property(property="postal_code", type="string", example="1043"),
     *                 @OA\Property(property="country", type="string", example="Argentina"),
     *                 @OA\Property(property="phone", type="string", example="+5491112345678")
     *             ),
     *             @OA\Property(
     *                 property="billing_address",
     *                 type="object",
     *                 description="Dirección de facturación (opcional)"
     *             ),
     *             @OA\Property(property="notes", type="string", example="Entregar después de las 18:00", description="Notas adicionales")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Pedido creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Pedido creado correctamente. Se ha enviado un email de confirmación."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="order",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="order_number", type="string", example="ORD-2024-001"),
     *                     @OA\Property(property="subtotal", type="number", format="float", example=2999.97),
     *                     @OA\Property(property="shipping_cost", type="number", format="float", example=500.00),
     *                     @OA\Property(property="tax_amount", type="number", format="float", example=599.99),
     *                     @OA\Property(property="discount_amount", type="number", format="float", example=600.00),
     *                     @OA\Property(property="total_amount", type="number", format="float", example=3499.96)
     *                 ),
     *                 @OA\Property(property="email_sent", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en el proceso de checkout",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function confirm(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'shipping_method_id' => 'required|string|exists:shipping_methods,id',
                'payment_method_id' => 'required|string|exists:payment_methods,id',
                'shipping_address' => 'required|array',
                'shipping_address.street_address' => 'required|string',
                'shipping_address.city' => 'required|string',
                'shipping_address.state' => 'required|string',
                'shipping_address.postal_code' => 'required|string',
                'shipping_address.country' => 'required|string',
                'billing_address' => 'nullable|array',
                'billing_address.street_address' => 'required_with:billing_address|string',
                'billing_address.city' => 'required_with:billing_address|string',
                'billing_address.state' => 'required_with:billing_address|string',
                'billing_address.postal_code' => 'required_with:billing_address|string',
                'billing_address.country' => 'required_with:billing_address|string',
                'coupon_code' => 'nullable|string',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            return DB::transaction(function () use ($request, $user) {
                // Obtener carrito
                $cart = Cart::where('user_id', $user->id)->with(['items.product'])->first();

                if (!$cart || $cart->items->isEmpty()) {
                    throw new \Exception('El carrito está vacío');
                }

                // Verificar stock disponible considerando reservas
                foreach ($cart->items as $item) {
                    $availableStock = $this->stockReservationService->getAvailableStock($item->product->id);
                    if ($availableStock < $item->quantity) {
                        throw new \Exception("Stock insuficiente para el producto '{$item->product->name}' (disponible: {$availableStock}, solicitado: {$item->quantity})");
                    }
                }

                // Obtener método de envío
                $shippingMethod = ShippingMethod::findOrFail($request->shipping_method_id);
                $shippingCost = $shippingMethod->cost;

                // Calcular totales
                $subtotal = $cart->getTotal();
                $taxAmount = $this->calculateTax($subtotal);
                $discountAmount = 0;
                $couponId = null;

                // Aplicar cupón si existe
                if ($request->filled('coupon_code')) {
                    $coupon = Coupon::where('code', $request->coupon_code)
                        ->where('is_active', true)
                        ->where('expires_at', '>', now())
                        ->lockForUpdate()
                        ->first();

                    if ($coupon) {
                        // Verificar uso máximo
                        $usageCount = $coupon->usages()->where('user_id', $user->id)->count();
                        if ($usageCount >= $coupon->max_uses_per_user) {
                            throw new \Exception('Ya has usado este cupón el máximo de veces permitido');
                        }

                        // Verificar monto mínimo
                        if ($subtotal < $coupon->minimum_amount) {
                            throw new \Exception("El monto mínimo para usar este cupón es $" . number_format($coupon->minimum_amount, 2));
                        }

                        // Calcular descuento
                        if ($coupon->discount_type === 'percentage') {
                            $discountAmount = ($subtotal * $coupon->discount_value) / 100;
                        } else {
                            $discountAmount = $coupon->discount_value;
                        }

                        $discountAmount = min($discountAmount, $subtotal);
                        $couponId = $coupon->id;
                    }
                }

                $totalAmount = $subtotal + $shippingCost + $taxAmount - $discountAmount;

                // Crear el pedido
                $order = Order::create([
                    'order_number' => $this->generateOrderNumber(),
                    'user_id' => $user->id,
                    'status' => 'pending',
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'shipping_cost' => $shippingCost,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'currency' => 'USD',
                    'payment_status' => 'pending',
                    'payment_method' => $request->payment_method_id,
                    'shipping_address' => $request->shipping_address,
                    'billing_address' => $request->billing_address ?: $request->shipping_address,
                    'notes' => $request->notes,
                ]);

                // Crear items del pedido y reservar stock
                foreach ($cart->items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'product_sku' => $item->product->sku,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->price,
                        'total_price' => $item->getSubtotal(),
                    ]);

                    // Crear reserva de stock para el pedido
                    $this->stockReservationService->createReservation(
                        $item->product_id,
                        $item->quantity,
                        $order->id,
                        $user->id,
                        null,
                        30, // 30 minutos de expiración
                        [
                            'order_item_id' => $item->id,
                            'price' => $item->price,
                        ]
                    );
                }

                // Registrar uso del cupón si existe
                if ($couponId) {
                    $coupon->usages()->create([
                        'user_id' => $user->id,
                        'order_id' => $order->id,
                        'discount_amount' => $discountAmount,
                    ]);
                }

                // Guardar dirección como principal si es la primera compra del usuario
                $this->saveUserAddressIfFirstOrder($user, $request->shipping_address);

                // Limpiar carrito
                $cart->items()->delete();
                $cart->delete();

                // Enviar email de confirmación (en cola para no bloquear la respuesta)
                $this->emailService->queueOrderConfirmation($order);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'order' => [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'status' => $order->status,
                            'total_amount' => $order->total_amount,
                            'created_at' => $order->created_at->toISOString(),
                        ],
                        'summary' => [
                            'subtotal' => $order->subtotal,
                            'shipping_cost' => $order->shipping_cost,
                            'tax_amount' => $order->tax_amount,
                            'discount_amount' => $order->discount_amount,
                            'total_amount' => $order->total_amount,
                        ],
                        'email_sent' => true
                    ],
                    'message' => 'Pedido creado correctamente. Se ha enviado un email de confirmación.'
                ], 201);

            });

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/checkout/shipping-methods",
     *     summary="Obtener métodos de envío",
     *     description="Obtiene todos los métodos de envío activos disponibles",
     *     tags={"Checkout"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Métodos de envío obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Métodos de envío obtenidos correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string", example="Envío Estándar"),
     *                     @OA\Property(property="description", type="string", example="Entrega en 3-5 días hábiles"),
     *                     @OA\Property(property="cost", type="number", format="float", example=500.00),
     *                     @OA\Property(property="estimated_days", type="integer", example=5),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function shippingMethods(): JsonResponse
    {
        try {
            $shippingMethods = ShippingMethod::where('is_active', true)->get();

            return response()->json([
                'success' => true,
                'data' => $shippingMethods,
                'message' => 'Métodos de envío obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener métodos de envío: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/checkout/payment-methods",
     *     summary="Obtener métodos de pago",
     *     description="Obtiene todos los métodos de pago activos disponibles",
     *     tags={"Checkout"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Métodos de pago obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Métodos de pago obtenidos correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string", example="Tarjeta de Crédito"),
     *                     @OA\Property(property="type", type="string", example="credit_card"),
     *                     @OA\Property(property="description", type="string", example="Pago con tarjeta de crédito"),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function paymentMethods(): JsonResponse
    {
        try {
            $paymentMethods = PaymentMethod::where('is_active', true)->get();

            return response()->json([
                'success' => true,
                'data' => $paymentMethods,
                'message' => 'Métodos de pago obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener métodos de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/checkout/validate-coupon",
     *     summary="Validar cupón",
     *     description="Valida un código de cupón y calcula el descuento aplicable",
     *     tags={"Checkout"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"coupon_code","subtotal"},
     *             @OA\Property(property="coupon_code", type="string", example="DESCUENTO20", description="Código del cupón a validar"),
     *             @OA\Property(property="subtotal", type="number", format="float", example=2999.97, description="Subtotal del carrito para validar monto mínimo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cupón válido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cupón válido"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="coupon",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="code", type="string", example="DESCUENTO20"),
     *                     @OA\Property(property="name", type="string", example="Descuento 20%"),
     *                     @OA\Property(property="description", type="string", example="20% de descuento en toda la compra"),
     *                     @OA\Property(property="discount_type", type="string", example="percentage"),
     *                     @OA\Property(property="discount_value", type="number", format="float", example=20.0),
     *                     @OA\Property(property="discount_amount", type="number", format="float", example=599.99),
     *                     @OA\Property(property="minimum_amount", type="number", format="float", example=1000.00)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cupón no válido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cupón no válido o expirado")
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
    public function validateCoupon(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'coupon_code' => 'required|string',
                'subtotal' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $coupon = Coupon::where('code', $request->coupon_code)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            if (!$coupon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cupón no válido o expirado'
                ], 400);
            }

            // Verificar uso máximo por usuario
            $usageCount = $coupon->usages()->where('user_id', $user->id)->count();
            if ($usageCount >= $coupon->max_uses_per_user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya has usado este cupón el máximo de veces permitido'
                ], 400);
            }

            // Verificar monto mínimo
            if ($request->subtotal < $coupon->minimum_amount) {
                return response()->json([
                    'success' => false,
                    'message' => "El monto mínimo para usar este cupón es $" . number_format($coupon->minimum_amount, 2)
                ], 400);
            }

            // Calcular descuento
            $subtotal = $request->subtotal;
            if ($coupon->discount_type === 'percentage') {
                $discountAmount = ($subtotal * $coupon->discount_value) / 100;
            } else {
                $discountAmount = $coupon->discount_value;
            }

            $discountAmount = min($discountAmount, $subtotal);

            return response()->json([
                'success' => true,
                'data' => [
                    'coupon' => [
                        'id' => $coupon->id,
                        'code' => $coupon->code,
                        'name' => $coupon->name,
                        'description' => $coupon->description,
                        'discount_type' => $coupon->discount_type,
                        'discount_value' => $coupon->discount_value,
                        'discount_amount' => $discountAmount,
                        'minimum_amount' => $coupon->minimum_amount,
                    ]
                ],
                'message' => 'Cupón válido'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar cupón: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular impuestos (método simplificado)
     */
    private function calculateTax(float $subtotal): float
    {
        // Implementación simplificada - 10% de impuestos
        // En producción, esto debería integrarse con un servicio de cálculo de impuestos
        return $subtotal * 0.10;
    }

    /**
     * Generar número de pedido único
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $timestamp = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        
        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Guardar dirección del usuario si es su primera compra
     */
    private function saveUserAddressIfFirstOrder($user, array $shippingAddress): void
    {
        // Verificar si el usuario ya tiene direcciones guardadas
        $existingAddressesCount = Address::where('user_id', $user->id)->count();
        
        if ($existingAddressesCount === 0) {
            // Primera compra - guardar dirección como principal
            Address::create([
                'user_id' => $user->id,
                'title' => 'Dirección Principal',
                'street_address' => $shippingAddress['street_address'] ?? '',
                'city' => $shippingAddress['city'] ?? '',
                'state' => $shippingAddress['state'] ?? '',
                'postal_code' => $shippingAddress['postal_code'] ?? '',
                'country' => $shippingAddress['country'] ?? 'Argentina',
                'is_default' => true,
            ]);
        }
    }
}
