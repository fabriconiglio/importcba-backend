<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test de listar productos
     */
    public function test_can_list_products(): void
    {
        $products = Product::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'sku',
                            'price',
                            'sale_price',
                            'effective_price',
                            'stock_quantity',
                            'is_active',
                            'slug',
                            'category',
                            'brand'
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
                'message' => 'Productos obtenidos correctamente'
            ]);
    }

    /**
     * Test de listar productos con filtros
     */
    public function test_can_list_products_with_filters(): void
    {
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        
        $products = Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/products?category_id=' . $category->id . '&brand_id=' . $brand->id . '&active=true');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Productos obtenidos correctamente'
            ]);
    }

    /**
     * Test de obtener producto específico
     */
    public function test_can_get_specific_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson('/api/v1/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'sku',
                    'price',
                    'sale_price',
                    'effective_price',
                    'stock_quantity',
                    'is_active',
                    'slug',
                    'category',
                    'brand',
                    'images'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Producto obtenido correctamente',
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name
                ]
            ]);
    }

    /**
     * Test de obtener producto por slug
     */
    public function test_can_get_product_by_slug(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/products/slug/' . $product->slug);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Producto obtenido correctamente',
                'data' => [
                    'id' => $product->id,
                    'slug' => $product->slug
                ]
            ]);
    }

    /**
     * Test de crear producto (requiere autenticación)
     */
    public function test_can_create_product(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $token = $user->createToken('test-token')->plainTextToken;

        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $productData = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'sku' => 'TEST-001',
            'price' => 99.99,
            'sale_price' => 79.99,
            'stock_quantity' => 100,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/v1/products', $productData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'sku',
                    'price',
                    'sale_price',
                    'stock_quantity',
                    'is_active'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Producto creado correctamente'
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 99.99
        ]);
    }

    /**
     * Test de crear producto sin autenticación
     */
    public function test_cannot_create_product_without_authentication(): void
    {
        $productData = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'sku' => 'TEST-001',
            'price' => 99.99
        ];

        $response = $this->postJson('/api/v1/products', $productData);

        $response->assertStatus(401);
    }

    /**
     * Test de actualizar producto
     */
    public function test_can_update_product(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $token = $user->createToken('test-token')->plainTextToken;

        $product = Product::factory()->create();

        $updateData = [
            'name' => 'Updated Product Name',
            'price' => 149.99,
            'description' => 'Updated description'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson('/api/v1/products/' . $product->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Producto actualizado correctamente',
                'data' => [
                    'name' => 'Updated Product Name',
                    'price' => 149.99
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'price' => 149.99
        ]);
    }

    /**
     * Test de eliminar producto
     */
    public function test_can_delete_product(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $token = $user->createToken('test-token')->plainTextToken;

        $product = Product::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->deleteJson('/api/v1/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Producto eliminado correctamente'
            ]);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id
        ]);
    }

    /**
     * Test de búsqueda de productos
     */
    public function test_can_search_products(): void
    {
        $product = Product::factory()->create([
            'name' => 'iPhone 15 Pro',
            'description' => 'Latest iPhone model'
        ]);

        $response = $this->getJson('/api/v1/products?search=iPhone');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Productos obtenidos correctamente'
            ]);
    }

    /**
     * Test de productos por categoría
     */
    public function test_can_get_products_by_category(): void
    {
        $category = Category::factory()->create();
        $products = Product::factory()->count(3)->create([
            'category_id' => $category->id
        ]);

        $response = $this->getJson('/api/v1/category/' . $category->slug);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Productos obtenidos correctamente'
            ]);
    }

    /**
     * Test de productos por marca
     */
    public function test_can_get_products_by_brand(): void
    {
        $brand = Brand::factory()->create();
        $products = Product::factory()->count(3)->create([
            'brand_id' => $brand->id
        ]);

        $response = $this->getJson('/api/v1/brand/' . $brand->slug);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Productos obtenidos correctamente'
            ]);
    }

    /**
     * Test de productos por categoría y marca
     */
    public function test_can_get_products_by_category_and_brand(): void
    {
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        $products = Product::factory()->count(2)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id
        ]);

        $response = $this->getJson('/api/v1/category/' . $category->slug . '/brand/' . $brand->slug);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Productos obtenidos correctamente'
            ]);
    }

    /**
     * Test de validación al crear producto
     */
    public function test_cannot_create_product_with_invalid_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $token = $user->createToken('test-token')->plainTextToken;

        $productData = [
            'name' => '', // Nombre vacío
            'price' => -10, // Precio negativo
            'sku' => 'TEST-001'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/v1/products', $productData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Error de validación'
            ]);
    }

    /**
     * Test de producto no encontrado
     */
    public function test_cannot_get_nonexistent_product(): void
    {
        $response = $this->getJson('/api/v1/products/nonexistent-id');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Producto no encontrado'
            ]);
    }
}
