<?php

namespace App\Http\Controllers\API;

/**
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     title="Respuesta de Éxito",
 *     description="Estructura estándar para respuestas exitosas",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operación exitosa"),
 *     @OA\Property(property="data", type="object", nullable=true)
 * )
 * 
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     title="Respuesta de Error",
 *     description="Estructura estándar para respuestas de error",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Error en la operación"),
 *     @OA\Property(property="errors", type="object", nullable=true)
 * )
 * 
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     title="Respuesta de Error de Validación",
 *     description="Estructura para errores de validación",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Error de validación"),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         @OA\Property(property="field", type="array", @OA\Items(type="string", example="El campo es requerido"))
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="PaginationResponse",
 *     title="Respuesta Paginada",
 *     description="Estructura para respuestas con paginación",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Datos obtenidos exitosamente"),
 *     @OA\Property(property="data", type="array", @OA\Items(type="object")),
 *     @OA\Property(
 *         property="pagination",
 *         type="object",
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="last_page", type="integer", example=5),
 *         @OA\Property(property="per_page", type="integer", example=15),
 *         @OA\Property(property="total", type="integer", example=75),
 *         @OA\Property(property="from", type="integer", example=1),
 *         @OA\Property(property="to", type="integer", example=15),
 *         @OA\Property(property="has_more_pages", type="boolean", example=true)
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="User",
 *     title="Usuario",
 *     description="Modelo de usuario",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="name", type="string", example="Juan Pérez"),
 *     @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
 * )
 * 
 * @OA\Schema(
 *     schema="Product",
 *     title="Producto",
 *     description="Modelo de producto",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="name", type="string", example="iPhone 15 Pro"),
 *     @OA\Property(property="description", type="string", example="El último iPhone con características avanzadas"),
 *     @OA\Property(property="sku", type="string", example="IPHONE15PRO-128"),
 *     @OA\Property(property="price", type="number", format="float", example=999.99),
 *     @OA\Property(property="original_price", type="number", format="float", example=1099.99),
 *     @OA\Property(property="effective_price", type="number", format="float", example=999.99),
 *     @OA\Property(property="stock_quantity", type="integer", example=50),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="slug", type="string", example="iphone-15-pro"),
 *     @OA\Property(property="category_id", type="string", format="uuid"),
 *     @OA\Property(property="brand_id", type="string", format="uuid"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="category",
 *         type="object",
 *         @OA\Property(property="id", type="string", format="uuid"),
 *         @OA\Property(property="name", type="string", example="Electrónicos"),
 *         @OA\Property(property="slug", type="string", example="electronicos")
 *     ),
 *     @OA\Property(
 *         property="brand",
 *         type="object",
 *         @OA\Property(property="id", type="string", format="uuid"),
 *         @OA\Property(property="name", type="string", example="Apple"),
 *         @OA\Property(property="slug", type="string", example="apple")
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="Category",
 *     title="Categoría",
 *     description="Modelo de categoría",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="name", type="string", example="Electrónicos"),
 *     @OA\Property(property="description", type="string", example="Productos electrónicos y tecnología"),
 *     @OA\Property(property="slug", type="string", example="electronicos"),
 *     @OA\Property(property="image", type="string", example="https://example.com/category.jpg", nullable=true),
 *     @OA\Property(property="parent_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="sort_order", type="integer", example=1),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(
 *         property="parent",
 *         ref="#/components/schemas/Category",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="children",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Category")
 *     ),
 *     @OA\Property(
 *         property="products",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Product")
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="Cart",
 *     title="Carrito",
 *     description="Modelo de carrito de compras",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="user_id", type="string", format="uuid"),
 *     @OA\Property(property="session_id", type="string", nullable=true),
 *     @OA\Property(property="expires_at", type="string", format="date-time"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/CartItem")
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="CartItem",
 *     title="Item del Carrito",
 *     description="Modelo de item del carrito",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="cart_id", type="string", format="uuid"),
 *     @OA\Property(property="product_id", type="string", format="uuid"),
 *     @OA\Property(property="quantity", type="integer", example=2),
 *     @OA\Property(property="price", type="number", format="float", example=999.99),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="product",
 *         ref="#/components/schemas/Product"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="Order",
 *     title="Pedido",
 *     description="Modelo de pedido",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="order_number", type="string", example="ORD-2024-001"),
 *     @OA\Property(property="user_id", type="string", format="uuid"),
 *     @OA\Property(property="status", type="string", example="pending"),
 *     @OA\Property(property="subtotal", type="number", format="float", example=1999.98),
 *     @OA\Property(property="shipping_cost", type="number", format="float", example=15.00),
 *     @OA\Property(property="tax_amount", type="number", format="float", example=200.00),
 *     @OA\Property(property="discount_amount", type="number", format="float", example=100.00),
 *     @OA\Property(property="total_amount", type="number", format="float", example=2114.98),
 *     @OA\Property(property="coupon_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/OrderItem")
 *     ),
 *     @OA\Property(
 *         property="shipping_address",
 *         type="object",
 *         @OA\Property(property="street_address", type="string"),
 *         @OA\Property(property="city", type="string"),
 *         @OA\Property(property="state", type="string"),
 *         @OA\Property(property="postal_code", type="string"),
 *         @OA\Property(property="country", type="string")
 *     ),
 *     @OA\Property(
 *         property="billing_address",
 *         type="object",
 *         @OA\Property(property="street_address", type="string"),
 *         @OA\Property(property="city", type="string"),
 *         @OA\Property(property="state", type="string"),
 *         @OA\Property(property="postal_code", type="string"),
 *         @OA\Property(property="country", type="string")
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="OrderItem",
 *     title="Item del Pedido",
 *     description="Modelo de item del pedido",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="order_id", type="string", format="uuid"),
 *     @OA\Property(property="product_id", type="string", format="uuid"),
 *     @OA\Property(property="product_name", type="string", example="iPhone 15 Pro"),
 *     @OA\Property(property="product_sku", type="string", example="IPHONE15PRO-128"),
 *     @OA\Property(property="quantity", type="integer", example=2),
 *     @OA\Property(property="unit_price", type="number", format="float", example=999.99),
 *     @OA\Property(property="total_price", type="number", format="float", example=1999.98),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Coupon",
 *     title="Cupón",
 *     description="Modelo de cupón",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="name", type="string", example="Descuento 10%"),
 *     @OA\Property(property="description", type="string", example="10% de descuento en toda la tienda"),
 *     @OA\Property(property="code", type="string", example="DESCUENTO10"),
 *     @OA\Property(property="type", type="string", enum={"percentage", "fixed_amount"}, example="percentage"),
 *     @OA\Property(property="value", type="number", format="float", example=10.00),
 *     @OA\Property(property="min_amount", type="number", format="float", example=100.00),
 *     @OA\Property(property="max_uses", type="integer", example=100),
 *     @OA\Property(property="max_uses_per_user", type="integer", example=1),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="starts_at", type="string", format="date-time"),
 *     @OA\Property(property="expires_at", type="string", format="date-time"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="PaymentMethod",
 *     title="Método de Pago",
 *     description="Modelo de método de pago",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="name", type="string", example="Tarjeta de Crédito"),
 *     @OA\Property(property="type", type="string", enum={"credit_card", "debit_card", "mercadopago", "paypal"}, example="credit_card"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="ShippingMethod",
 *     title="Método de Envío",
 *     description="Modelo de método de envío",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="name", type="string", example="Envío Estándar"),
 *     @OA\Property(property="description", type="string", example="Entrega en 3-5 días hábiles"),
 *     @OA\Property(property="cost", type="number", format="float", example=15.00),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Address",
 *     title="Dirección",
 *     description="Modelo de dirección",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="user_id", type="string", format="uuid"),
 *     @OA\Property(property="first_name", type="string", example="Juan"),
 *     @OA\Property(property="last_name", type="string", example="Pérez"),
 *     @OA\Property(property="street_address", type="string", example="Av. Corrientes 1234"),
 *     @OA\Property(property="city", type="string", example="Buenos Aires"),
 *     @OA\Property(property="state", type="string", example="Buenos Aires"),
 *     @OA\Property(property="postal_code", type="string", example="1043"),
 *     @OA\Property(property="country", type="string", example="Argentina"),
 *     @OA\Property(property="phone", type="string", example="+5491112345678"),
 *     @OA\Property(property="is_default", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Brand",
 *     title="Marca",
 *     description="Modelo de marca",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="name", type="string", example="Apple"),
 *     @OA\Property(property="description", type="string", example="Empresa líder en tecnología e innovación"),
 *     @OA\Property(property="slug", type="string", example="apple"),
 *     @OA\Property(property="logo_url", type="string", format="url", example="https://example.com/logo.png", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(
 *         property="products",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Product")
 *     )
 * )
 */
class SwaggerSchemas
{
    // Esta clase solo contiene anotaciones de Swagger
    // No necesita métodos
} 