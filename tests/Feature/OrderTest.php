<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Address;
use App\Models\ShippingMethod;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test de listar pedidos del usuario autenticado
     */
    public function test_can_list_user_orders(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $orders = Order::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'order_number',
                            'status',
                            'payment_status',
                            'subtotal',
                            'total_amount',
                            'created_at'
                        ]
                    ],
                    'current_page',
                    'last_page',
                    'per_page',
                    'total'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Pedidos obtenidos correctamente'
            ]);
    }

    /**
     * Test de obtener pedido específico
     */
    public function test_can_get_specific_order(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $order = Order::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => $product->price
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/orders/' . $order->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'order_number',
                    'status',
                    'payment_status',
                    'subtotal',
                    'total_amount',
                    'shipping_address',
                    'billing_address',
                    'items' => [
                        '*' => [
                            'id',
                            'product_name',
                            'quantity',
                            'unit_price',
                            'total_price'
                        ]
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Pedido obtenido correctamente'
            ]);
    }

    /**
     * Test de obtener pedidos por estado
     */
    public function test_can_get_orders_by_status(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $orders = Order::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'pending'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/orders/status/pending');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pedidos obtenidos correctamente'
            ]);
    }

    /**
     * Test de obtener estadísticas de pedidos
     */
    public function test_can_get_order_stats(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $orders = Order::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/orders/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_orders',
                    'total_spent',
                    'average_order_value',
                    'orders_by_status'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Estadísticas obtenidas correctamente'
            ]);
    }

    /**
     * Test de crear pedido desde carrito
     */
    public function test_can_create_order_from_cart(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        // Crear carrito con productos
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['stock_quantity' => 10]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price
        ]);

        // Crear dirección de envío
        $address = Address::factory()->create(['user_id' => $user->id]);
        
        // Crear método de envío
        $shippingMethod = ShippingMethod::factory()->create();
        
        // Crear método de pago
        $paymentMethod = PaymentMethod::factory()->create();

        $orderData = [
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'shipping_method_id' => $shippingMethod->id,
            'payment_method_id' => $paymentMethod->id,
            'notes' => 'Entregar después de las 18:00'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/v1/checkout/confirm', $orderData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'order' => [
                        'id',
                        'order_number',
                        'status',
                        'total_amount'
                    ],
                    'payment_url'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Pedido creado correctamente'
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => 'pending'
        ]);
    }

    /**
     * Test de crear pedido sin carrito
     */
    public function test_cannot_create_order_without_cart(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $address = Address::factory()->create(['user_id' => $user->id]);
        $shippingMethod = ShippingMethod::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $orderData = [
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'shipping_method_id' => $shippingMethod->id,
            'payment_method_id' => $paymentMethod->id
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/v1/checkout/confirm', $orderData);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'El carrito está vacío'
            ]);
    }

    /**
     * Test de crear pedido sin autenticación
     */
    public function test_cannot_create_order_without_authentication(): void
    {
        $orderData = [
            'shipping_address_id' => '550e8400-e29b-41d4-a716-446655440000',
            'billing_address_id' => '550e8400-e29b-41d4-a716-446655440000'
        ];

        $response = $this->postJson('/api/v1/checkout/confirm', $orderData);

        $response->assertStatus(401);
    }

    /**
     * Test de obtener pedido de otro usuario
     */
    public function test_cannot_get_other_user_order(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token = $user1->createToken('test-token')->plainTextToken;
        $order = Order::factory()->create(['user_id' => $user2->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/orders/' . $order->id);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ]);
    }

    /**
     * Test de calcular total del pedido
     */
    public function test_can_calculate_order_total(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 100]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100
        ]);

        $shippingMethod = ShippingMethod::factory()->create(['cost' => 15]);

        $calculationData = [
            'shipping_method_id' => $shippingMethod->id
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/v1/checkout/calculate', $calculationData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'subtotal',
                    'shipping_cost',
                    'tax_amount',
                    'total_amount'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Total calculado correctamente',
                'data' => [
                    'subtotal' => 200, // 100 * 2
                    'shipping_cost' => 15
                ]
            ]);
    }

    /**
     * Test de iniciar checkout
     */
    public function test_can_initiate_checkout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create();
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/checkout/initiate');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'cart',
                    'shipping_methods',
                    'payment_methods',
                    'addresses'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Checkout iniciado correctamente'
            ]);
    }

    /**
     * Test de obtener métodos de envío
     */
    public function test_can_get_shipping_methods(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $shippingMethods = ShippingMethod::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/checkout/shipping-methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'cost',
                        'estimated_days'
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Métodos de envío obtenidos correctamente'
            ]);
    }

    /**
     * Test de obtener métodos de pago
     */
    public function test_can_get_payment_methods(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $paymentMethods = PaymentMethod::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/checkout/payment-methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'is_active'
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Métodos de pago obtenidos correctamente'
            ]);
    }

    /**
     * Test de validar cupón
     */
    public function test_can_validate_coupon(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $couponData = [
            'coupon_code' => 'DESCUENTO20',
            'subtotal' => 1000
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/v1/checkout/validate-coupon', $couponData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'coupon' => [
                        'code',
                        'name',
                        'type',
                        'value',
                        'discount_amount'
                    ]
                ]
            ]);
    }

    /**
     * Test de pedido no encontrado
     */
    public function test_cannot_get_nonexistent_order(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/orders/nonexistent-id');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ]);
    }

    /**
     * Test de validación al crear pedido
     */
    public function test_cannot_create_order_with_invalid_data(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $orderData = [
            'shipping_address_id' => 'invalid-uuid',
            'billing_address_id' => 'invalid-uuid'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/v1/checkout/confirm', $orderData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Error de validación'
            ]);
    }
}
