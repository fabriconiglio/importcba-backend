# Documentación de la API Ecommerce

## 📋 Descripción General

La API de Ecommerce es una API RESTful completa que proporciona todas las funcionalidades necesarias para un sistema de comercio electrónico moderno. Está construida con Laravel 11 y utiliza OpenAPI/Swagger para la documentación.

## 🔗 Enlaces de Documentación

- **Swagger UI**: `/api/docs` - Interfaz interactiva de documentación
- **JSON de la API**: `/docs/api-docs.json` - Especificación OpenAPI en formato JSON
- **Estado de la API**: `/api/v1/docs/health` - Verificar estado de servicios
- **Endpoints disponibles**: `/api/v1/docs/endpoints` - Lista de todos los endpoints

## 🏗️ Arquitectura

### Tecnologías Utilizadas
- **Backend**: Laravel 11 (PHP 8.2+)
- **Base de Datos**: PostgreSQL
- **Autenticación**: Laravel Sanctum (JWT)
- **Documentación**: OpenAPI 3.0 / Swagger
- **Validación**: Laravel Validation
- **Paginación**: Laravel Pagination
- **Colas**: Laravel Queue (Database/Redis)

### Estructura de Respuestas
Todas las respuestas siguen un formato estándar:

```json
{
    "success": true,
    "message": "Operación exitosa",
    "data": {
        // Datos específicos de la respuesta
    }
}
```

### Respuestas de Error
```json
{
    "success": false,
    "message": "Descripción del error",
    "errors": {
        // Detalles de errores de validación (opcional)
    }
}
```

## 🔐 Autenticación

### Bearer Token
La API utiliza autenticación Bearer Token con Laravel Sanctum:

```bash
Authorization: Bearer {token}
```

### Endpoints de Autenticación
- `POST /api/v1/auth/register` - Registro de usuarios
- `POST /api/v1/auth/login` - Inicio de sesión
- `POST /api/v1/auth/logout` - Cerrar sesión
- `POST /api/v1/auth/refresh` - Renovar token
- `GET /api/v1/auth/me` - Obtener perfil del usuario

## 📦 Módulos Principales

### 1. Productos (`/api/v1/products`)
- **GET** `/products` - Listar productos con filtros
- **GET** `/products/{id}` - Obtener producto específico
- **POST** `/products` - Crear producto (Admin)
- **PUT** `/products/{id}` - Actualizar producto (Admin)
- **DELETE** `/products/{id}` - Eliminar producto (Admin)

**Filtros disponibles:**
- `category_id` - Filtrar por categoría
- `brand_id` - Filtrar por marca
- `search` - Búsqueda por nombre/descripción
- `price_min` / `price_max` - Rango de precios
- `featured` - Productos destacados
- `in_stock` - Disponibilidad de stock
- `sort` - Ordenamiento (newest, oldest, price_asc, etc.)

### 2. Catálogo (`/api/v1/catalog`)
- **GET** `/catalog/category/{slug}` - Productos por categoría
- **GET** `/catalog/brand/{slug}` - Productos por marca
- **GET** `/catalog/search` - Búsqueda avanzada

### 3. Carrito (`/api/v1/cart`)
- **GET** `/cart` - Obtener carrito del usuario
- **POST** `/cart/add` - Agregar producto al carrito
- **PUT** `/cart/items/{id}` - Actualizar cantidad
- **DELETE** `/cart/items/{id}` - Eliminar item
- **DELETE** `/cart/clear` - Vaciar carrito

### 4. Carrito Anónimo (`/api/v1/anonymous-cart`)
- **GET** `/anonymous-cart` - Obtener carrito anónimo
- **POST** `/anonymous-cart/add` - Agregar producto
- **PUT** `/anonymous-cart/items/{id}` - Actualizar item
- **DELETE** `/anonymous-cart/items/{id}` - Eliminar item
- **DELETE** `/anonymous-cart/clear` - Vaciar carrito

### 5. Merge de Carrito (`/api/v1/cart-merge`)
- **POST** `/cart-merge/merge` - Fusionar carritos
- **GET** `/cart-merge/anonymous-info` - Info del carrito anónimo
- **GET** `/cart-merge/stats` - Estadísticas (Admin)

### 6. Pedidos (`/api/v1/orders`)
- **GET** `/orders` - Listar pedidos del usuario
- **GET** `/orders/{id}` - Obtener pedido específico
- **POST** `/orders` - Crear pedido

### 7. Checkout (`/api/v1/checkout`)
- **POST** `/checkout/initiate` - Iniciar checkout
- **POST** `/checkout/confirm` - Confirmar pedido
- **GET** `/checkout/shipping-methods` - Métodos de envío
- **GET** `/checkout/payment-methods` - Métodos de pago

### 8. Pagos (`/api/v1/payments`)
- **POST** `/payments/process` - Procesar pago
- **GET** `/payments/{id}` - Obtener información de pago
- **POST** `/payments/{id}/refund` - Reembolsar pago
- **POST** `/payments/methods` - Crear método de pago

### 9. Cupones (`/api/v1/coupons`)
- **GET** `/coupons` - Listar cupones disponibles
- **POST** `/coupons/validate` - Validar cupón
- **POST** `/coupons/apply` - Aplicar cupón al carrito
- **DELETE** `/coupons/remove` - Remover cupón del carrito
- **GET** `/coupons/history` - Historial de uso

### 10. Emails (`/api/v1/emails`)
- **POST** `/emails/order/{id}/confirmation` - Enviar confirmación
- **POST** `/emails/password-reset` - Recuperación de contraseña
- **POST** `/emails/welcome/{userId}` - Email de bienvenida
- **GET** `/emails/check-configuration` - Verificar configuración
- **GET** `/emails/stats` - Estadísticas (Admin)

### 11. Stock (`/api/v1/stock-reservations`)
- **POST** `/stock-reservations` - Crear reserva
- **POST** `/stock-reservations/{id}/confirm` - Confirmar reserva
- **POST** `/stock-reservations/{id}/cancel` - Cancelar reserva
- **GET** `/stock-reservations/product/{id}/available` - Stock disponible
- **GET** `/stock-reservations/stats` - Estadísticas (Admin)

## 📊 Esquemas de Datos

### Producto
```json
{
    "id": "uuid",
    "name": "string",
    "description": "string",
    "sku": "string",
    "price": "number",
    "sale_price": "number|null",
    "effective_price": "number",
    "stock_quantity": "integer",
    "is_active": "boolean",
    "slug": "string",
    "category": {
        "id": "uuid",
        "name": "string",
        "slug": "string"
    },
    "brand": {
        "id": "uuid",
        "name": "string",
        "slug": "string"
    }
}
```

### Pedido
```json
{
    "id": "uuid",
    "order_number": "string",
    "status": "string",
    "subtotal": "number",
    "shipping_cost": "number",
    "tax_amount": "number",
    "discount_amount": "number",
    "total_amount": "number",
    "items": [
        {
            "id": "uuid",
            "product_name": "string",
            "quantity": "integer",
            "unit_price": "number",
            "total_price": "number"
        }
    ]
}
```

### Carrito
```json
{
    "id": "uuid",
    "items": [
        {
            "id": "uuid",
            "product": {
                "id": "uuid",
                "name": "string",
                "price": "number"
            },
            "quantity": "integer",
            "price": "number"
        }
    ],
    "total": "number",
    "total_items": "integer"
}
```

## 🔧 Configuración

### Variables de Entorno Requeridas
```env
# Aplicación
APP_NAME="Ecommerce API"
APP_URL=http://localhost:8000
APP_FRONTEND_URL=http://localhost:3000

# Base de Datos
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ecommerce
DB_USERNAME=postgres
DB_PASSWORD=password

# Autenticación
SANCTUM_STATEFUL_DOMAINS=localhost:3000

# Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=username
MAIL_PASSWORD=password
MAIL_FROM_ADDRESS=noreply@ecommerce.com
MAIL_FROM_NAME="${APP_NAME}"

# Colas
QUEUE_CONNECTION=database
QUEUE_DRIVER=database
```

### Comandos Útiles
```bash
# Generar documentación
php artisan l5-swagger:generate

# Limpiar caché
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders
php artisan db:seed

# Procesar colas
php artisan queue:work

# Verificar estado
php artisan l5-swagger:generate
```

## 🚀 Ejemplos de Uso

### 1. Obtener Productos
```bash
curl -X GET "http://localhost:8000/api/v1/products?page=1&per_page=10&category_id=uuid&sort=price_asc" \
  -H "Accept: application/json"
```

### 2. Agregar al Carrito
```bash
curl -X POST "http://localhost:8000/api/v1/cart/add" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "uuid",
    "quantity": 2
  }'
```

### 3. Iniciar Checkout
```bash
curl -X POST "http://localhost:8000/api/v1/checkout/initiate" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "shipping_address": {
      "street_address": "123 Main St",
      "city": "Buenos Aires",
      "state": "BA",
      "postal_code": "1001",
      "country": "Argentina"
    },
    "shipping_method_id": "uuid"
  }'
```

### 4. Confirmar Pedido
```bash
curl -X POST "http://localhost:8000/api/v1/checkout/confirm" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "payment_method_id": "uuid",
    "coupon_code": "DESCUENTO10",
    "notes": "Entregar después de las 18:00"
  }'
```

## 📈 Estadísticas de la API

- **Total de Endpoints**: 60+
- **Módulos**: 11
- **Esquemas de Datos**: 15+
- **Cobertura de Documentación**: 100%
- **Ejemplos de Uso**: Incluidos en Swagger UI

## 🛠️ Desarrollo

### Estructura de Archivos
```
app/
├── Http/
│   ├── Controllers/
│   │   └── API/
│   │       ├── BaseApiController.php
│   │       ├── ProductController.php
│   │       ├── CartController.php
│   │       ├── OrderController.php
│   │       └── ...
│   ├── Resources/
│   │   ├── ProductResource.php
│   │   ├── OrderResource.php
│   │   └── ...
│   └── Middleware/
├── Models/
├── Services/
└── Mail/

docs/
├── API_DOCUMENTATION.md
├── EMAIL_CONFIGURATION.md
├── BRAND_API.md
├── CATALOG_API.md
├── CHECKOUT_API.md
├── PAYMENT_API.md
├── COUPON_API.md
├── CART_MERGE_API.md
└── STOCK_RESERVATION_API.md
```

### Convenciones de Código
- **Controladores**: Extienden `BaseApiController`
- **Respuestas**: Usan métodos estándar (`successResponse`, `errorResponse`, etc.)
- **Validación**: Laravel Validation con mensajes personalizados
- **Documentación**: Anotaciones OpenAPI en cada método
- **Errores**: Manejo consistente con códigos HTTP apropiados

## 🔍 Monitoreo y Logs

### Endpoints de Monitoreo
- `/api/v1/docs/health` - Estado de servicios
- `/api/v1/docs/endpoints` - Lista de endpoints
- `/api/v1/emails/stats` - Estadísticas de emails
- `/api/v1/stock-reservations/stats` - Estadísticas de stock

### Logs
- **Laravel Logs**: `storage/logs/laravel.log`
- **Email Logs**: Configurables por driver
- **Queue Logs**: `storage/logs/queue.log`

## 📞 Soporte

Para soporte técnico o consultas sobre la API:

- **Email**: soporte@ecommerce.com
- **Documentación**: `/api/docs`
- **Estado de la API**: `/api/v1/docs/health`

---

**Versión**: 1.0.0  
**Última actualización**: Enero 2024  
**Laravel**: 11.x  
**PHP**: 8.2+ 