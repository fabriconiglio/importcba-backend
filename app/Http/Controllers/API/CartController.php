<?php

namespace App\Http\Controllers\API;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Carrito",
 *     description="Endpoints para gestión del carrito de compras"
 * )
 */
class CartController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/cart",
     *     summary="Obtener carrito del usuario",
     *     description="Retorna el carrito de compras del usuario autenticado con todos sus items",
     *     tags={"Carrito"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Carrito obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Carrito obtenido correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(
     *                     property="items",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(
     *                             property="product",
     *                             type="object",
     *                             @OA\Property(property="id", type="string", format="uuid"),
     *                             @OA\Property(property="name", type="string", example="iPhone 15 Pro"),
     *                             @OA\Property(property="slug", type="string", example="iphone-15-pro"),
     *                             @OA\Property(property="image", type="string", example="https://example.com/image.jpg"),
     *                             @OA\Property(property="category", type="string", example="Electrónicos"),
     *                             @OA\Property(property="brand", type="string", example="Apple")
     *                         ),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="price", type="number", format="float", example=999.99),
     *                         @OA\Property(property="original_price", type="number", format="float", example=1099.99),
     *                         @OA\Property(property="subtotal", type="number", format="float", example=1999.98),
     *                         @OA\Property(property="savings", type="number", format="float", example=200.00),
     *                         @OA\Property(property="has_discount", type="boolean", example=true),
     *                         @OA\Property(property="discount_percentage", type="number", format="float", example=18.18)
     *                     )
     *                 ),
     *                 @OA\Property(property="total_items", type="integer", example=5),
     *                 @OA\Property(property="total", type="number", format="float", example=4999.95),
     *                 @OA\Property(property="total_savings", type="number", format="float", example=500.00)
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
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $cart = $this->getOrCreateCart($user->id);

            // Cargar relaciones necesarias
            $cart->load(['items.product.category', 'items.product.brand']);

            // Transformar datos para el frontend
            $cartData = [
                'id' => $cart->id,
                'items' => $cart->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'slug' => $item->product->slug,
                            'image' => $item->product->primary_image_url,
                            'category' => $item->product->category?->name,
                            'brand' => $item->product->brand?->name,
                        ],
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'original_price' => $item->original_price,
                        'subtotal' => $item->getSubtotal(),
                        'savings' => $item->getSavings(),
                        'has_discount' => $item->hasDiscount(),
                        'discount_percentage' => $item->getDiscountPercentage(),
                    ];
                }),
                'total_items' => $cart->getTotalItems(),
                'total' => $cart->getTotal(),
                'total_savings' => $cart->getTotalSavings(),
            ];

            return response()->json([
                'success' => true,
                'data' => $cartData,
                'message' => 'Carrito obtenido correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener carrito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cart/add",
     *     summary="Agregar producto al carrito",
     *     description="Agrega un producto al carrito del usuario autenticado",
     *     tags={"Carrito"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id","quantity"},
     *             @OA\Property(property="product_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID del producto"),
     *             @OA\Property(property="quantity", type="integer", example=2, description="Cantidad a agregar")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Producto agregado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Producto agregado al carrito"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="cart_item",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="quantity", type="integer", example=2),
     *                     @OA\Property(property="price", type="number", format="float", example=999.99),
     *                     @OA\Property(property="subtotal", type="number", format="float", example=1999.98)
     *                 ),
     *                 @OA\Property(property="cart_total", type="number", format="float", example=4999.95),
     *                 @OA\Property(property="cart_items", type="integer", example=5)
     *             )
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
    public function addItem(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => ['required', 'string', 'exists:products,id'],
                'quantity' => ['required', 'integer', 'min:1'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $cart = $this->getOrCreateCart($user->id);
            $product = Product::findOrFail($request->product_id);

            // Verificar stock
            if ($product->stock_quantity < $request->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['No hay suficiente stock disponible.']
                ]);
            }

            // Buscar si el producto ya está en el carrito
            $cartItem = $cart->items()->where('product_id', $product->id)->first();

            if ($cartItem) {
                // Actualizar cantidad si ya existe
                $newQuantity = $cartItem->quantity + $request->quantity;
                if ($product->stock_quantity < $newQuantity) {
                    throw ValidationException::withMessages([
                        'quantity' => ['La cantidad total excede el stock disponible.']
                    ]);
                }
                $cartItem->updateQuantity($newQuantity);
            } else {
                // Crear nuevo item
                $cartItem = $cart->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                    'price' => $product->getEffectivePrice(),
                    'original_price' => $product->sale_price ? $product->price : null,
                ]);
            }

            // Extender expiración del carrito
            $cart->extendExpiration();

            return response()->json([
                'success' => true,
                'message' => 'Producto agregado al carrito',
                'data' => [
                    'cart_item' => [
                        'id' => $cartItem->id,
                        'quantity' => $cartItem->quantity,
                        'price' => $cartItem->price,
                        'subtotal' => $cartItem->getSubtotal(),
                    ],
                    'cart_total' => $cart->getTotal(),
                    'cart_items' => $cart->getTotalItems(),
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar producto al carrito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar cantidad de un item
     */
    public function updateItem(Request $request, string $itemId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'quantity' => ['required', 'integer', 'min:0'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $cart = $this->getOrCreateCart($user->id);
            
            $cartItem = $cart->items()->findOrFail($itemId);
            $product = $cartItem->product;

            // Verificar stock
            if ($request->quantity > 0 && $product->stock_quantity < $request->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['No hay suficiente stock disponible.']
                ]);
            }

            // Actualizar o eliminar item
            if ($request->quantity <= 0) {
                $cartItem->delete();
                $message = 'Producto eliminado del carrito';
            } else {
                $cartItem->updateQuantity($request->quantity);
                $message = 'Cantidad actualizada correctamente';
            }

            // Extender expiración del carrito
            $cart->extendExpiration();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'cart_total' => $cart->getTotal(),
                    'cart_items' => $cart->getTotalItems(),
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar carrito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un item del carrito
     */
    public function removeItem(Request $request, string $itemId): JsonResponse
    {
        try {
            $user = $request->user();
            $cart = $this->getOrCreateCart($user->id);
            
            $cartItem = $cart->items()->findOrFail($itemId);
            $cartItem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado del carrito',
                'data' => [
                    'cart_total' => $cart->getTotal(),
                    'cart_items' => $cart->getTotalItems(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar producto del carrito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar el carrito
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $cart = $this->getOrCreateCart($user->id);
            
            $cart->clear();

            return response()->json([
                'success' => true,
                'message' => 'Carrito limpiado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar carrito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener cantidad de items en el carrito
     */
    public function count(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $cart = $this->getOrCreateCart($user->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'count' => $cart->getTotalItems()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener cantidad de items: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener total del carrito
     */
    public function total(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $cart = $this->getOrCreateCart($user->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $cart->getTotal(),
                    'savings' => $cart->getTotalSavings()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener total del carrito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener o crear carrito para un usuario
     */
    private function getOrCreateCart(string $userId): Cart
    {
        $cart = Cart::where('user_id', $userId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $userId,
                'expires_at' => now()->addDays(7),
            ]);
        }

        return $cart;
    }
}
