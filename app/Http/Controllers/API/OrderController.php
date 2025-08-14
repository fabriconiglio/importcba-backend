<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @OA\Tag(
 *     name="Orders",
 *     description="Endpoints para gestión de pedidos del usuario"
 * )
 */
class OrderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/orders",
     *     summary="Listar pedidos del usuario",
     *     description="Obtiene una lista paginada de todos los pedidos del usuario autenticado",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pedidos obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Pedidos obtenidos correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array", @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="order_number", type="string", example="ORD-2024-001"),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="payment_status", type="string", example="pending"),
     *                     @OA\Property(property="total_amount", type="number", format="float", example=2999.97),
     *                     @OA\Property(property="currency", type="string", example="USD"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="items_count", type="integer", example=3),
     *                     @OA\Property(
     *                         property="items",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="string", format="uuid"),
     *                             @OA\Property(property="product_name", type="string", example="iPhone 15 Pro"),
     *                             @OA\Property(property="product_sku", type="string", example="IPHONE15PRO-128"),
     *                             @OA\Property(property="quantity", type="integer", example=2),
     *                             @OA\Property(property="unit_price", type="number", format="float", example=999.99),
     *                             @OA\Property(property="total_price", type="number", format="float", example=1999.98),
     *                             @OA\Property(
     *                                 property="product",
     *                                 type="object",
     *                                 @OA\Property(property="id", type="string", format="uuid"),
     *                                 @OA\Property(property="name", type="string", example="iPhone 15 Pro"),
     *                                 @OA\Property(property="slug", type="string", example="iphone-15-pro"),
     *                                 @OA\Property(property="image", type="string", example="https://example.com/image.jpg")
     *                             )
     *                         )
     *                     )
     *                 )),
     *                 @OA\Property(property="first_page_url", type="string"),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="last_page_url", type="string"),
     *                 @OA\Property(property="next_page_url", type="string", nullable=true),
     *                 @OA\Property(property="path", type="string"),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="prev_page_url", type="string", nullable=true),
     *                 @OA\Property(property="to", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=50)
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
     * @OA\Post(
     *     path="/api/v1/orders",
     *     summary="Crear pedido (No permitido)",
     *     description="Este endpoint no está disponible. Use el endpoint de checkout para crear pedidos",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=405,
     *         description="Método no permitido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Use el endpoint de checkout para crear pedidos")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/orders/{id}",
     *     summary="Obtener pedido específico",
     *     description="Obtiene los detalles completos de un pedido específico del usuario autenticado",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del pedido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pedido obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Pedido obtenido correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="order_number", type="string", example="ORD-2024-001"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="payment_status", type="string", example="pending"),
     *                 @OA\Property(property="subtotal", type="number", format="float", example=2999.97),
     *                 @OA\Property(property="tax_amount", type="number", format="float", example=299.99),
     *                 @OA\Property(property="shipping_cost", type="number", format="float", example=500.00),
     *                 @OA\Property(property="discount_amount", type="number", format="float", example=0.00),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=3799.96),
     *                 @OA\Property(property="currency", type="string", example="USD"),
     *                 @OA\Property(
     *                     property="shipping_address",
     *                     type="object",
     *                     @OA\Property(property="street_address", type="string", example="Av. Corrientes 1234"),
     *                     @OA\Property(property="city", type="string", example="Buenos Aires"),
     *                     @OA\Property(property="state", type="string", example="Buenos Aires"),
     *                     @OA\Property(property="postal_code", type="string", example="1043"),
     *                     @OA\Property(property="country", type="string", example="Argentina")
     *                 ),
     *                 @OA\Property(
     *                     property="billing_address",
     *                     type="object",
     *                     @OA\Property(property="street_address", type="string", example="Av. Corrientes 1234"),
     *                     @OA\Property(property="city", type="string", example="Buenos Aires"),
     *                     @OA\Property(property="state", type="string", example="Buenos Aires"),
     *                     @OA\Property(property="postal_code", type="string", example="1043"),
     *                     @OA\Property(property="country", type="string", example="Argentina")
     *                 ),
     *                 @OA\Property(property="notes", type="string", example="Entregar después de las 18:00"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="items",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="product_name", type="string", example="iPhone 15 Pro"),
     *                         @OA\Property(property="product_sku", type="string", example="IPHONE15PRO-128"),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="unit_price", type="number", format="float", example=999.99),
     *                         @OA\Property(property="total_price", type="number", format="float", example=1999.98),
     *                         @OA\Property(
     *                             property="product",
     *                             type="object",
     *                             @OA\Property(property="id", type="string", format="uuid"),
     *                             @OA\Property(property="name", type="string", example="iPhone 15 Pro"),
     *                             @OA\Property(property="slug", type="string", example="iphone-15-pro"),
     *                             @OA\Property(property="image", type="string", example="https://example.com/image.jpg"),
     *                             @OA\Property(property="description", type="string", example="El último iPhone con características avanzadas")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pedido no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
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
     * @OA\Put(
     *     path="/api/v1/orders/{id}",
     *     summary="Actualizar pedido (No permitido)",
     *     description="Los pedidos no se pueden actualizar directamente desde la API",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del pedido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=405,
     *         description="Método no permitido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Los pedidos no se pueden actualizar desde la API")
     *         )
     *     )
     * )
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
     * @OA\Delete(
     *     path="/api/v1/orders/{id}",
     *     summary="Eliminar pedido (No permitido)",
     *     description="Los pedidos no se pueden eliminar directamente desde la API",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del pedido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=405,
     *         description="Método no permitido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Los pedidos no se pueden eliminar desde la API")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/orders/status/{status}",
     *     summary="Obtener pedidos por estado",
     *     description="Obtiene una lista paginada de pedidos del usuario filtrados por estado",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="path",
     *         description="Estado del pedido",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             enum={"pending", "processing", "shipped", "delivered", "cancelled"},
     *             example="pending"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pedidos obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Pedidos con estado 'pending' obtenidos correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array", @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="order_number", type="string", example="ORD-2024-001"),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="payment_status", type="string", example="pending"),
     *                     @OA\Property(property="total_amount", type="number", format="float", example=2999.97),
     *                     @OA\Property(property="currency", type="string", example="USD"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="items_count", type="integer", example=3)
     *                 )),
     *                 @OA\Property(property="first_page_url", type="string"),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="last_page_url", type="string"),
     *                 @OA\Property(property="next_page_url", type="string", nullable=true),
     *                 @OA\Property(property="path", type="string"),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="prev_page_url", type="string", nullable=true),
     *                 @OA\Property(property="to", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=50)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Estado de pedido no válido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Estado de pedido no válido")
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
     * @OA\Get(
     *     path="/api/v1/orders/stats",
     *     summary="Obtener estadísticas de pedidos",
     *     description="Obtiene estadísticas completas de los pedidos del usuario autenticado",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Estadísticas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Estadísticas de pedidos obtenidas correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_orders", type="integer", example=25),
     *                 @OA\Property(property="pending_orders", type="integer", example=3),
     *                 @OA\Property(property="processing_orders", type="integer", example=2),
     *                 @OA\Property(property="shipped_orders", type="integer", example=5),
     *                 @OA\Property(property="delivered_orders", type="integer", example=12),
     *                 @OA\Property(property="cancelled_orders", type="integer", example=3),
     *                 @OA\Property(property="total_spent", type="number", format="float", example=74999.25),
     *                 @OA\Property(property="average_order_value", type="number", format="float", example=2999.97)
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
