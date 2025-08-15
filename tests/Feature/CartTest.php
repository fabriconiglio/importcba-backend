<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test de obtener carrito del usuario autenticado
     */
    public function test_can_get_user_cart(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create();
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'items' => [
                        '*' => [
                            'id',
                            'product' => [
                                'id',
                                'name',
                                'slug',
                                'image'
                            ],
                            'quantity',
                            'price',
                            'subtotal'
                        ]
                    ],
                    'total_items',
                    'total'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Carrito obtenido correctamente'
            ]);
    }

    /**
     * Test de agregar producto al carrito
     */
    public function test_can_add_product_to_cart(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $cartData = [
            'product_id' => $product->id,
            'quantity' => 2
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/v1/cart/add', $cartData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'item' => [
                        'id',
                        'product' => [
                            'id',
                            'name',
                            'slug',
                            'image'
                        ],
                        'quantity',
                        'price',
                        'subtotal'
                    ],
                    'cart_total_items',
                    'cart_total'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Producto agregado al carrito correctamente'
            ]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2
        ]);
    }

    /**
     * Test de agregar producto al carrito sin autenticación
     */
    public function test_cannot_add_product_to_cart_without_authentication(): void
    {
        $product = Product::factory()->create();

        $cartData = [
            'product_id' => $product->id,
            'quantity' => 2
        ];

        $response = $this->postJson('/api/v1/cart/add', $cartData);

        $response->assertStatus(401);
    }

    /**
     * Test de agregar producto con stock insuficiente
     */
    public function test_cannot_add_product_with_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $product = Product::factory()->create(['stock_quantity' => 1]);

        $cartData = [
            'product_id' => $product->id,
            'quantity' => 5
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/v1/cart/add', $cartData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Error de validación'
            ]);
    }

    /**
     * Test de actualizar cantidad de producto en el carrito
     */
    public function test_can_update_cart_item_quantity(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['stock_quantity' => 10]);
        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1
        ]);

        $updateData = ['quantity' => 3];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson('/api/v1/cart/update/' . $cartItem->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cantidad actualizada correctamente'
            ]);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 3
        ]);
    }

    /**
     * Test de remover producto del carrito
     */
    public function test_can_remove_product_from_cart(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create();
        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->deleteJson('/api/v1/cart/remove/' . $cartItem->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Producto removido del carrito correctamente'
            ]);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id
        ]);
    }

    /**
     * Test de limpiar carrito
     */
    public function test_can_clear_cart(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $products = Product::factory()->count(3)->create();
        
        foreach ($products as $product) {
            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_id' => $product->id
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->deleteJson('/api/v1/cart/clear');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Carrito limpiado correctamente'
            ]);

        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id
        ]);
    }

    /**
     * Test de obtener cantidad de items en el carrito
     */
    public function test_can_get_cart_count(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $products = Product::factory()->count(3)->create();
        
        foreach ($products as $product) {
            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => 2
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/cart/count');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'count'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'count' => 6 // 3 productos * 2 cantidad cada uno
                ]
            ]);
    }

    /**
     * Test de obtener total del carrito
     */
    public function test_can_get_cart_total(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $product1 = Product::factory()->create(['price' => 100]);
        $product2 = Product::factory()->create(['price' => 50]);
        
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'price' => 100
        ]);
        
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'price' => 50
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/cart/total');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'savings'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'total' => 250 // (100 * 2) + (50 * 1)
                ]
            ]);
    }

    /**
     * Test de carrito vacío
     */
    public function test_can_get_empty_cart(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Carrito obtenido correctamente',
                'data' => [
                    'items' => [],
                    'total_items' => 0,
                    'total' => 0
                ]
            ]);
    }

    /**
     * Test de validación al agregar producto
     */
    public function test_cannot_add_product_with_invalid_data(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $cartData = [
            'product_id' => 'invalid-uuid',
            'quantity' => -1
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/v1/cart/add', $cartData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Error de validación'
            ]);
    }

    /**
     * Test de agregar producto inexistente
     */
    public function test_cannot_add_nonexistent_product(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $cartData = [
            'product_id' => '550e8400-e29b-41d4-a716-446655440000',
            'quantity' => 1
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/v1/cart/add', $cartData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Error de validación'
            ]);
    }
} 