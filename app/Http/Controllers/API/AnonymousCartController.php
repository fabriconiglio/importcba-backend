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

class AnonymousCartController extends Controller
{
    private CartMergeService $cartMergeService;

    public function __construct(CartMergeService $cartMergeService)
    {
        $this->cartMergeService = $cartMergeService;
    }

    /**
     * Obtener el carrito anónimo
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
     * Agregar item al carrito anónimo
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
     * Actualizar cantidad de un item en el carrito anónimo
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
     * Remover item del carrito anónimo
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
     * Limpiar carrito anónimo
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
