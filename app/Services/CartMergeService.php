<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartMergeService
{
    /**
     * Merge del carrito anónimo con el carrito del usuario
     */
    public function mergeAnonymousCart(User $user, string $sessionId): array
    {
        try {
            DB::beginTransaction();

            // Obtener carrito anónimo por session_id
            $anonymousCart = Cart::where('session_id', $sessionId)
                ->whereNull('user_id')
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->with('items.product')
                ->first();

            if (!$anonymousCart || $anonymousCart->isEmpty()) {
                DB::commit();
                return [
                    'success' => true,
                    'message' => 'No hay carrito anónimo para mergear',
                    'merged_items' => 0,
                    'conflicts' => 0
                ];
            }

            // Obtener o crear carrito del usuario
            $userCart = $this->getOrCreateUserCart($user->id);

            $mergedItems = 0;
            $conflicts = 0;
            $conflictDetails = [];

            // Procesar cada item del carrito anónimo
            foreach ($anonymousCart->items as $anonymousItem) {
                $result = $this->mergeCartItem($userCart, $anonymousItem);
                
                if ($result['merged']) {
                    $mergedItems++;
                } else {
                    $conflicts++;
                    $conflictDetails[] = $result['reason'];
                }
            }

            // Eliminar carrito anónimo después del merge
            $anonymousCart->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Carrito mergeado correctamente',
                'merged_items' => $mergedItems,
                'conflicts' => $conflicts,
                'conflict_details' => $conflictDetails,
                'user_cart_id' => $userCart->id
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al mergear carrito: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Error al mergear carrito: ' . $e->getMessage(),
                'merged_items' => 0,
                'conflicts' => 0
            ];
        }
    }

    /**
     * Obtener o crear carrito para un usuario
     */
    private function getOrCreateUserCart(string $userId): Cart
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

    /**
     * Mergear un item específico del carrito
     */
    private function mergeCartItem(Cart $userCart, CartItem $anonymousItem): array
    {
        // Verificar si el producto existe y tiene stock
        if (!$anonymousItem->product) {
            return [
                'merged' => false,
                'reason' => 'Producto no encontrado: ' . $anonymousItem->product_id
            ];
        }

        $product = $anonymousItem->product;

        // Verificar stock disponible
        if ($product->stock_quantity < $anonymousItem->quantity) {
            return [
                'merged' => false,
                'reason' => 'Stock insuficiente para: ' . $product->name
            ];
        }

        // Buscar si el producto ya existe en el carrito del usuario
        $existingItem = $userCart->items()
            ->where('product_id', $anonymousItem->product_id)
            ->first();

        if ($existingItem) {
            // Producto ya existe, sumar cantidades
            $newQuantity = $existingItem->quantity + $anonymousItem->quantity;
            
            // Verificar stock total
            if ($product->stock_quantity < $newQuantity) {
                return [
                    'merged' => false,
                    'reason' => 'Stock insuficiente al sumar cantidades para: ' . $product->name
                ];
            }

            // Actualizar cantidad y precio (usar el precio más bajo)
            $existingItem->quantity = $newQuantity;
            $existingItem->price = min($existingItem->price, $anonymousItem->price);
            
            // Mantener el precio original más alto para mostrar el descuento
            if ($anonymousItem->original_price && $anonymousItem->original_price > $existingItem->original_price) {
                $existingItem->original_price = $anonymousItem->original_price;
            }
            
            $existingItem->save();

            // Eliminar el item anónimo
            $anonymousItem->delete();

            return [
                'merged' => true,
                'reason' => 'Cantidad sumada al item existente'
            ];

        } else {
            // Producto no existe, crear nuevo item
            $newItem = CartItem::create([
                'cart_id' => $userCart->id,
                'product_id' => $anonymousItem->product_id,
                'quantity' => $anonymousItem->quantity,
                'price' => $anonymousItem->price,
                'original_price' => $anonymousItem->original_price,
            ]);

            return [
                'merged' => true,
                'reason' => 'Nuevo item agregado'
            ];
        }
    }

    /**
     * Obtener carrito anónimo por session_id
     */
    public function getAnonymousCart(string $sessionId): ?Cart
    {
        return Cart::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with('items.product')
            ->first();
    }

    /**
     * Crear carrito anónimo
     */
    public function createAnonymousCart(string $sessionId): Cart
    {
        return Cart::create([
            'session_id' => $sessionId,
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * Obtener o crear carrito anónimo
     */
    public function getOrCreateAnonymousCart(string $sessionId): Cart
    {
        $cart = $this->getAnonymousCart($sessionId);

        if (!$cart) {
            $cart = $this->createAnonymousCart($sessionId);
        }

        return $cart;
    }

    /**
     * Limpiar carritos anónimos expirados
     */
    public function cleanExpiredAnonymousCarts(): int
    {
        $expiredCarts = Cart::whereNull('user_id')
            ->where('expires_at', '<', now())
            ->get();

        $deletedCount = 0;

        foreach ($expiredCarts as $cart) {
            $cart->items()->delete();
            $cart->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    /**
     * Obtener estadísticas de carritos anónimos
     */
    public function getAnonymousCartStats(): array
    {
        $totalAnonymousCarts = Cart::whereNull('user_id')->count();
        $activeAnonymousCarts = Cart::whereNull('user_id')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();
        $expiredAnonymousCarts = Cart::whereNull('user_id')
            ->where('expires_at', '<', now())
            ->count();

        return [
            'total' => $totalAnonymousCarts,
            'active' => $activeAnonymousCarts,
            'expired' => $expiredAnonymousCarts,
        ];
    }
} 