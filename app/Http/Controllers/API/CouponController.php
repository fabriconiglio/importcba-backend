<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    /**
     * Listar cupones activos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $coupons = Coupon::where('is_active', true)
                ->where('starts_at', '<=', now())
                ->where('expires_at', '>', now())
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $coupons->getCollection()->transform(function ($coupon) use ($user) {
                return [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'name' => $coupon->name ?? 'Cupón de descuento',
                    'description' => $coupon->description,
                    'type' => $coupon->type,
                    'value' => $coupon->value,
                    'minimum_amount' => $coupon->minimum_amount,
                    'usage_limit' => $coupon->usage_limit,
                    'used_count' => $coupon->used_count,
                    'remaining_uses' => $coupon->usage_limit ? $coupon->usage_limit - $coupon->used_count : null,
                    'starts_at' => $coupon->starts_at?->toISOString(),
                    'expires_at' => $coupon->expires_at?->toISOString(),
                    'is_available' => $this->isCouponAvailableForUser($coupon, $user),
                    'user_usage_count' => $coupon->usages()->where('user_id', $user->id)->count(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $coupons,
                'message' => 'Cupones obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener cupones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar cupón
     */
    public function validate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'required|string|max:50',
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
            $code = strtoupper(trim($request->code));
            $subtotal = $request->subtotal;

            // Buscar cupón
            $coupon = Coupon::where('code', $code)
                ->where('is_active', true)
                ->first();

            if (!$coupon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cupón no válido o inactivo'
                ], 400);
            }

            // Validar fecha de inicio
            if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este cupón aún no está disponible'
                ], 400);
            }

            // Validar fecha de expiración
            if ($coupon->expires_at && $coupon->expires_at->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este cupón ha expirado'
                ], 400);
            }

            // Validar límite de uso global
            if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este cupón ya no está disponible (límite de uso alcanzado)'
                ], 400);
            }

            // Validar monto mínimo
            if ($coupon->minimum_amount && $subtotal < $coupon->minimum_amount) {
                return response()->json([
                    'success' => false,
                    'message' => "El monto mínimo para usar este cupón es $" . number_format($coupon->minimum_amount, 2)
                ], 400);
            }

            // Validar uso por usuario
            $userUsageCount = $coupon->usages()->where('user_id', $user->id)->count();
            if ($userUsageCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya has usado este cupón'
                ], 400);
            }

            // Calcular descuento
            $discountAmount = $this->calculateDiscount($coupon, $subtotal);

            return response()->json([
                'success' => true,
                'data' => [
                    'coupon' => [
                        'id' => $coupon->id,
                        'code' => $coupon->code,
                        'name' => $coupon->name ?? 'Cupón de descuento',
                        'description' => $coupon->description,
                        'type' => $coupon->type,
                        'value' => $coupon->value,
                        'minimum_amount' => $coupon->minimum_amount,
                        'discount_amount' => $discountAmount,
                        'subtotal' => $subtotal,
                        'final_amount' => $subtotal - $discountAmount,
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
     * Aplicar cupón a un pedido
     */
    public function apply(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'required|string|max:50',
                'order_id' => 'required|string|exists:orders,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $code = strtoupper(trim($request->code));

            // Verificar que el pedido pertenece al usuario
            $order = $user->orders()->where('id', $request->order_id)->firstOrFail();

            // Verificar que el pedido no esté pagado
            if ($order->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede aplicar cupón a un pedido ya pagado'
                ], 400);
            }

            // Verificar que el pedido no tenga cupón aplicado
            if ($order->coupon_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido ya tiene un cupón aplicado'
                ], 400);
            }

            // Buscar y validar cupón
            $coupon = Coupon::where('code', $code)
                ->where('is_active', true)
                ->first();

            if (!$coupon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cupón no válido o inactivo'
                ], 400);
            }

            // Validar disponibilidad del cupón
            $validationResult = $this->validateCouponForOrder($coupon, $order, $user);
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validationResult['message']
                ], 400);
            }

            // Calcular descuento
            $discountAmount = $this->calculateDiscount($coupon, $order->subtotal);

            // Aplicar descuento al pedido
            $order->update([
                'coupon_id' => $coupon->id,
                'discount_amount' => $discountAmount,
                'total_amount' => $order->subtotal + $order->shipping_cost + $order->tax_amount - $discountAmount,
            ]);

            // Registrar uso del cupón
            CouponUsage::create([
                'coupon_id' => $coupon->id,
                'user_id' => $user->id,
                'order_id' => $order->id,
                'discount_amount' => $discountAmount,
                'used_at' => now(),
            ]);

            // Incrementar contador de uso
            $coupon->increment('used_count');

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'subtotal' => $order->subtotal,
                        'discount_amount' => $discountAmount,
                        'total_amount' => $order->total_amount,
                    ],
                    'coupon' => [
                        'id' => $coupon->id,
                        'code' => $coupon->code,
                        'name' => $coupon->name ?? 'Cupón de descuento',
                        'type' => $coupon->type,
                        'value' => $coupon->value,
                    ]
                ],
                'message' => 'Cupón aplicado correctamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aplicar cupón: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remover cupón de un pedido
     */
    public function remove(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|string|exists:orders,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            // Verificar que el pedido pertenece al usuario
            $order = $user->orders()->where('id', $request->order_id)->firstOrFail();

            // Verificar que el pedido no esté pagado
            if ($order->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede remover cupón de un pedido ya pagado'
                ], 400);
            }

            // Verificar que el pedido tenga cupón aplicado
            if (!$order->coupon_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido no tiene cupón aplicado'
                ], 400);
            }

            $coupon = $order->coupon;
            $discountAmount = $order->discount_amount;

            // Remover cupón del pedido
            $order->update([
                'coupon_id' => null,
                'discount_amount' => 0,
                'total_amount' => $order->subtotal + $order->shipping_cost + $order->tax_amount,
            ]);

            // Remover registro de uso
            CouponUsage::where('coupon_id', $coupon->id)
                ->where('order_id', $order->id)
                ->delete();

            // Decrementar contador de uso
            $coupon->decrement('used_count');

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'subtotal' => $order->subtotal,
                        'discount_amount' => 0,
                        'total_amount' => $order->total_amount,
                    ]
                ],
                'message' => 'Cupón removido correctamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al remover cupón: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de cupones del usuario
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $usages = CouponUsage::where('user_id', $user->id)
                ->with(['coupon', 'order'])
                ->orderBy('used_at', 'desc')
                ->paginate(10);

            $usages->getCollection()->transform(function ($usage) {
                return [
                    'id' => $usage->id,
                    'coupon' => [
                        'id' => $usage->coupon->id,
                        'code' => $usage->coupon->code,
                        'name' => $usage->coupon->name ?? 'Cupón de descuento',
                        'type' => $usage->coupon->type,
                        'value' => $usage->coupon->value,
                    ],
                    'order' => [
                        'id' => $usage->order->id,
                        'order_number' => $usage->order->order_number,
                        'total_amount' => $usage->order->total_amount,
                    ],
                    'discount_amount' => $usage->discount_amount,
                    'used_at' => $usage->used_at->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $usages,
                'message' => 'Historial de cupones obtenido correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular descuento
     */
    private function calculateDiscount(Coupon $coupon, float $subtotal): float
    {
        if ($coupon->type === 'percentage') {
            $discount = ($subtotal * $coupon->value) / 100;
        } else {
            $discount = $coupon->value;
        }

        // No permitir descuento mayor al subtotal
        return min($discount, $subtotal);
    }

    /**
     * Validar cupón para un pedido específico
     */
    private function validateCouponForOrder(Coupon $coupon, $order, User $user): array
    {
        // Validar fecha de inicio
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return ['valid' => false, 'message' => 'Este cupón aún no está disponible'];
        }

        // Validar fecha de expiración
        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return ['valid' => false, 'message' => 'Este cupón ha expirado'];
        }

        // Validar límite de uso global
        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return ['valid' => false, 'message' => 'Este cupón ya no está disponible (límite de uso alcanzado)'];
        }

        // Validar monto mínimo
        if ($coupon->minimum_amount && $order->subtotal < $coupon->minimum_amount) {
            return ['valid' => false, 'message' => "El monto mínimo para usar este cupón es $" . number_format($coupon->minimum_amount, 2)];
        }

        // Validar uso por usuario
        $userUsageCount = $coupon->usages()->where('user_id', $user->id)->count();
        if ($userUsageCount > 0) {
            return ['valid' => false, 'message' => 'Ya has usado este cupón'];
        }

        return ['valid' => true, 'message' => 'Cupón válido'];
    }

    /**
     * Verificar si un cupón está disponible para un usuario
     */
    private function isCouponAvailableForUser(Coupon $coupon, User $user): bool
    {
        // Verificar fechas
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return false;
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return false;
        }

        // Verificar límite de uso global
        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return false;
        }

        // Verificar uso por usuario
        $userUsageCount = $coupon->usages()->where('user_id', $user->id)->count();
        if ($userUsageCount > 0) {
            return false;
        }

        return true;
    }
}
