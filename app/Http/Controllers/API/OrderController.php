<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $orders = Order::where('user_id', $user->id)
                ->with(['items.product'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $orders->getCollection()->transform(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'total_amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'created_at' => $order->created_at->toISOString(),
                    'items_count' => $order->items->count(),
                    'items' => $order->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_name' => $item->product_name,
                            'product_sku' => $item->product_sku,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total_price' => $item->total_price,
                            'product' => $item->product ? [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'slug' => $item->product->slug,
                                'image' => $item->product->primary_image_url,
                            ] : null,
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Pedidos obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedidos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Este método no se usa directamente, se usa el CheckoutController
        return response()->json([
            'success' => false,
            'message' => 'Use el endpoint de checkout para crear pedidos'
        ], 405);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = request()->user();
            
            $order = Order::where('id', $id)
                ->where('user_id', $user->id)
                ->with(['items.product', 'user'])
                ->firstOrFail();

            $orderData = [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'subtotal' => $order->subtotal,
                'tax_amount' => $order->tax_amount,
                'shipping_cost' => $order->shipping_cost,
                'discount_amount' => $order->discount_amount,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,
                'shipping_address' => $order->shipping_address,
                'billing_address' => $order->billing_address,
                'notes' => $order->notes,
                'created_at' => $order->created_at->toISOString(),
                'updated_at' => $order->updated_at->toISOString(),
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product_name,
                        'product_sku' => $item->product_sku,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price,
                        'product' => $item->product ? [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'slug' => $item->product->slug,
                            'image' => $item->product->primary_image_url,
                            'description' => $item->product->description,
                        ] : null,
                    ];
                }),
            ];

            return response()->json([
                'success' => true,
                'data' => $orderData,
                'message' => 'Pedido obtenido correctamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        // Los pedidos no se pueden actualizar directamente desde la API
        return response()->json([
            'success' => false,
            'message' => 'Los pedidos no se pueden actualizar desde la API'
        ], 405);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        // Los pedidos no se pueden eliminar directamente desde la API
        return response()->json([
            'success' => false,
            'message' => 'Los pedidos no se pueden eliminar desde la API'
        ], 405);
    }

    /**
     * Obtener pedidos por estado
     */
    public function byStatus(Request $request, string $status): JsonResponse
    {
        try {
            $user = $request->user();
            
            $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
            
            if (!in_array($status, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estado de pedido no válido'
                ], 400);
            }

            $orders = Order::where('user_id', $user->id)
                ->where('status', $status)
                ->with(['items.product'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $orders->getCollection()->transform(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'total_amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'created_at' => $order->created_at->toISOString(),
                    'items_count' => $order->items->count(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => "Pedidos con estado '{$status}' obtenidos correctamente"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedidos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de pedidos del usuario
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $stats = [
                'total_orders' => Order::where('user_id', $user->id)->count(),
                'pending_orders' => Order::where('user_id', $user->id)->where('status', 'pending')->count(),
                'processing_orders' => Order::where('user_id', $user->id)->where('status', 'processing')->count(),
                'shipped_orders' => Order::where('user_id', $user->id)->where('status', 'shipped')->count(),
                'delivered_orders' => Order::where('user_id', $user->id)->where('status', 'delivered')->count(),
                'cancelled_orders' => Order::where('user_id', $user->id)->where('status', 'cancelled')->count(),
                'total_spent' => Order::where('user_id', $user->id)->where('status', '!=', 'cancelled')->sum('total_amount'),
                'average_order_value' => Order::where('user_id', $user->id)->where('status', '!=', 'cancelled')->avg('total_amount'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas de pedidos obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }
}
