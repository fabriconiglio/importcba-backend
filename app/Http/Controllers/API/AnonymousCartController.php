<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartMergeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Anonymous Cart",
 *     description="Endpoints para gestión del carrito de compras anónimo"
 * )
 */
class AnonymousCartController extends Controller
{
    private CartMergeService $cartMergeService;

    public function __construct(CartMergeService $cartMergeService)
    {
        $this->cartMergeService = $cartMergeService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/anonymous-cart",
     *     summary="Obtener el carrito anónimo",
     *     description="Obtiene el contenido completo del carrito de compras anónimo",
     *     tags={"Anonymous Cart"},
     *     @OA\Parameter(
     *         name="X-Session-ID",
     *         in="header",
     *         description="ID de sesión del usuario anónimo",
     *         required=true,
     *         @OA\Schema(type="string", example="session_1234567890")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Carrito anónimo obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Carrito anónimo obtenido correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", nullable=true),
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
     *                             @OA\Property(property="name", type="string", example="Producto Ejemplo"),
     *                             @OA\Property(property="slug", type="string", example="producto-ejemplo"),
     *                             @OA\Property(property="image", type="string", example="https://example.com/image.jpg"),
     *                             @OA\Property(property="category", type="string", example="Electrónicos"),
     *                             @OA\Property(property="brand", type="string", example="Marca Ejemplo")
     *                         ),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="price", type="number", format="float", example=29.99),
     *                         @OA\Property(property="original_price", type="number", format="float", example=39.99, nullable=true),
     *                         @OA\Property(property="subtotal", type="number", format="float", example=59.98),
     *                         @OA\Property(property="savings", type="number", format="float", example=20.00),
     *                         @OA\Property(property="has_discount", type="boolean", example=true),
     *                         @OA\Property(property="discount_percentage", type="number", format="float", example=25.0)
     *                     )
     *                 ),
     *                 @OA\Property(property="total_items", type="integer", example=5),
     *                 @OA\Property(property="total", type="number", format="float", example=149.95),
     *                 @OA\Property(property="total_savings", type="number", format="float", example=50.00)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Session ID requerido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Session ID requerido")
     *         )
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
            $sessionId = $request->header('X-Session-ID');
            
            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session ID requerido'
                ], 400);
            }

            $cart = $this->cartMergeService->getAnonymousCart($sessionId);

            if (!$cart || $cart->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => null,
                        'items' => [],
                        'total_items' => 0,
                        'total' => 0,
                        'total_savings' => 0,
                    ],
                    'message' => 'Carrito anónimo vacío'
                ]);
            }

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
                'message' => 'Carrito anónimo obtenido correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener carrito anónimo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/anonymous-cart/add",
     *     summary="Agregar item al carrito anónimo",
     *     description="Agrega un producto al carrito de compras anónimo",
     *     tags={"Anonymous Cart"},
     *     @OA\Parameter(
     *         name="X-Session-ID",
     *         in="header",
     *         description="ID de sesión del usuario anónimo",
     *         required=true,
     *         @OA\Schema(type="string", example="session_1234567890")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id","quantity"},
     *             @OA\Property(property="product_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID del producto"),
     *             @OA\Property(property="quantity", type="integer", minimum=1, example=2, description="Cantidad a agregar")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Producto agregado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Producto agregado al carrito anónimo correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="item",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(
     *                         property="product",
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="name", type="string", example="Producto Ejemplo"),
     *                         @OA\Property(property="slug", type="string", example="producto-ejemplo"),
     *                         @OA\Property(property="image", type="string", example="https://example.com/image.jpg")
     *                     ),
     *                     @OA\Property(property="quantity", type="integer", example=2),
     *                     @OA\Property(property="price", type="number", format="float", example=29.99),
     *                     @OA\Property(property="original_price", type="number", format="float", example=39.99, nullable=true),
     *                     @OA\Property(property="subtotal", type="number", format="float", example=59.98)
     *                 ),
     *                 @OA\Property(property="cart_total_items", type="integer", example=5),
     *                 @OA\Property(property="cart_total", type="number", format="float", example=149.95)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Session ID requerido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Session ID requerido")
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

            $sessionId = $request->header('X-Session-ID');
            
            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session ID requerido'
                ], 400);
            }

            $cart = $this->cartMergeService->getOrCreateAnonymousCart($sessionId);
            $product = Product::findOrFail($request->product_id);

            // Verificar stock
            if ($product->stock_quantity < $request->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['No hay suficiente stock disponible.']
                ]);
            }

            // Buscar si el producto ya está en el carrito
            $existingItem = $cart->items()
                ->where('product_id', $request->product_id)
                ->first();

            if ($existingItem) {
                // Actualizar cantidad
                $newQuantity = $existingItem->quantity + $request->quantity;
                
                if ($product->stock_quantity < $newQuantity) {
                    throw ValidationException::withMessages([
                        'quantity' => ['No hay suficiente stock disponible para la cantidad total.']
                    ]);
                }

                $existingItem->update(['quantity' => $newQuantity]);
                $item = $existingItem;
            } else {
                // Crear nuevo item
                $item = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'price' => $product->getEffectivePrice(),
                    'original_price' => $product->sale_price ? $product->price : null,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'item' => [
                        'id' => $item->id,
                        'product' => [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => $product->slug,
                            'image' => $product->primary_image_url,
                        ],
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'original_price' => $item->original_price,
                        'subtotal' => $item->getSubtotal(),
                    ],
                    'cart_total_items' => $cart->getTotalItems(),
                    'cart_total' => $cart->getTotal(),
                ],
                'message' => 'Producto agregado al carrito anónimo correctamente'
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
                'message' => 'Error al agregar producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/anonymous-cart/items/{itemId}",
     *     summary="Actualizar cantidad de un item en el carrito anónimo",
     *     description="Actualiza la cantidad de un producto específico en el carrito anónimo",
     *     tags={"Anonymous Cart"},
     *     @OA\Parameter(
     *         name="X-Session-ID",
     *         in="header",
     *         description="ID de sesión del usuario anónimo",
     *         required=true,
     *         @OA\Schema(type="string", example="session_1234567890")
     *     ),
     *     @OA\Parameter(
     *         name="itemId",
     *         in="path",
     *         description="ID del item del carrito",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="integer", minimum=0, example=3, description="Nueva cantidad (0 para remover)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cantidad actualizada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cantidad actualizada correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="cart_total_items", type="integer", example=4),
     *                 @OA\Property(property="cart_total", type="number", format="float", example=119.96)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Session ID requerido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Session ID requerido")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Carrito o item no encontrado",
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
    public function updateItem(Request $request, string $itemId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'quantity' => ['required', 'integer', 'min:1'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sessionId = $request->header('X-Session-ID');
            
            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session ID requerido'
                ], 400);
            }

            $cart = $this->cartMergeService->getAnonymousCart($sessionId);
            
            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carrito anónimo no encontrado'
                ], 404);
            }

            $item = $cart->items()->findOrFail($itemId);
            $product = $item->product;

            // Verificar stock
            if ($product->stock_quantity < $request->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['No hay suficiente stock disponible.']
                ]);
            }

            if ($request->quantity === 0) {
                $item->delete();
                $message = 'Producto removido del carrito anónimo';
            } else {
                $item->update(['quantity' => $request->quantity]);
                $message = 'Cantidad actualizada correctamente';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'cart_total_items' => $cart->getTotalItems(),
                    'cart_total' => $cart->getTotal(),
                ],
                'message' => $message
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
                'message' => 'Error al actualizar item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/anonymous-cart/items/{itemId}",
     *     summary="Remover item del carrito anónimo",
     *     description="Elimina un producto específico del carrito anónimo",
     *     tags={"Anonymous Cart"},
     *     @OA\Parameter(
     *         name="X-Session-ID",
     *         in="header",
     *         description="ID de sesión del usuario anónimo",
     *         required=true,
     *         @OA\Schema(type="string", example="session_1234567890")
     *     ),
     *     @OA\Parameter(
     *         name="itemId",
     *         in="path",
     *         description="ID del item del carrito",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Producto removido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Producto removido del carrito anónimo correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="cart_total_items", type="integer", example=3),
     *                 @OA\Property(property="cart_total", type="number", format="float", example=89.97)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Session ID requerido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Session ID requerido")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Carrito o item no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function removeItem(Request $request, string $itemId): JsonResponse
    {
        try {
            $sessionId = $request->header('X-Session-ID');
            
            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session ID requerido'
                ], 400);
            }

            $cart = $this->cartMergeService->getAnonymousCart($sessionId);
            
            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carrito anónimo no encontrado'
                ], 404);
            }

            $item = $cart->items()->findOrFail($itemId);
            $item->delete();

            return response()->json([
                'success' => true,
                'data' => [
                    'cart_total_items' => $cart->getTotalItems(),
                    'cart_total' => $cart->getTotal(),
                ],
                'message' => 'Producto removido del carrito anónimo correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al remover producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/anonymous-cart/clear",
     *     summary="Limpiar carrito anónimo",
     *     description="Elimina todos los productos del carrito anónimo",
     *     tags={"Anonymous Cart"},
     *     @OA\Parameter(
     *         name="X-Session-ID",
     *         in="header",
     *         description="ID de sesión del usuario anónimo",
     *         required=true,
     *         @OA\Schema(type="string", example="session_1234567890")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Carrito limpiado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Carrito anónimo limpiado correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Session ID requerido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Session ID requerido")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->header('X-Session-ID');
            
            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session ID requerido'
                ], 400);
            }

            $cart = $this->cartMergeService->getAnonymousCart($sessionId);
            
            if ($cart) {
                $cart->clear();
            }

            return response()->json([
                'success' => true,
                'message' => 'Carrito anónimo limpiado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar carrito anónimo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener cantidad de items en el carrito anónimo
     */
    public function count(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->header('X-Session-ID');
            
            if (!$sessionId) {
                return response()->json([
                    'success' => true,
                    'data' => ['count' => 0]
                ]);
            }

            $cart = $this->cartMergeService->getAnonymousCart($sessionId);

            return response()->json([
                'success' => true,
                'data' => [
                    'count' => $cart ? $cart->getTotalItems() : 0
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener cantidad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener total del carrito anónimo
     */
    public function total(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->header('X-Session-ID');
            
            if (!$sessionId) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total' => 0,
                        'savings' => 0
                    ]
                ]);
            }

            $cart = $this->cartMergeService->getAnonymousCart($sessionId);

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $cart ? $cart->getTotal() : 0,
                    'savings' => $cart ? $cart->getTotalSavings() : 0
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener total: ' . $e->getMessage()
            ], 500);
        }
    }
}
