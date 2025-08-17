# API de Checkout y Pedidos

## Descripción
API completa para el proceso de checkout, incluyendo cálculo de envío, totales, cupones y confirmación de pedidos.

## Endpoints de Checkout

### 1. Iniciar Checkout
**GET** `/api/v1/checkout/initiate`

Obtiene la información inicial del checkout: carrito, direcciones, métodos de envío y pago.

#### Headers requeridos:
```
Authorization: Bearer {token}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "cart_summary": {
      "total_items": 3,
      "subtotal": 299.97,
      "shipping_cost": 0,
      "tax_amount": 29.99,
      "discount_amount": 0,
      "total_amount": 329.96
    },
    "addresses": [
      {
        "id": "addr-1",
        "title": "Casa",
        "street_address": "Calle Principal 123",
        "city": "Ciudad",
        "state": "Estado",
        "postal_code": "12345",
        "country": "País",
        "is_default": true
      }
    ],
    "shipping_methods": [
      {
        "id": "ship-1",
        "name": "Envío Estándar",
        "description": "Envío estándar de 3-5 días hábiles",
        "cost": 5.99,
        "estimated_days": 5,
        "is_active": true
      }
    ],
    "payment_methods": [
      {
        "id": "pay-1",
        "name": "Tarjeta de Crédito",
        "type": "credit_card",
        "is_active": true,
        "configuration": {
          "processor": "stripe",
          "supported_cards": ["visa", "mastercard", "amex"],
          "requires_cvv": true
        }
      }
    ],
    "items": [
      {
        "id": "item-1",
        "product": {
          "id": "prod-1",
          "name": "iPhone 15 Pro",
          "slug": "iphone-15-pro",
          "image": "https://example.com/iphone.jpg",
          "stock_quantity": 10
        },
        "quantity": 1,
        "price": 999.99,
        "original_price": null,
        "subtotal": 999.99,
        "has_discount": false,
        "discount_percentage": 0
      }
    ]
  },
  "message": "Checkout iniciado correctamente"
}
```

### 2. Calcular Totales
**POST** `/api/v1/checkout/calculate`

Calcula los totales del pedido incluyendo envío, impuestos y descuentos.

#### Headers requeridos:
```
Authorization: Bearer {token}
Content-Type: application/json
```

#### Body:
```json
{
  "shipping_method_id": "ship-1",
  "shipping_address": {
    "street_address": "Calle Principal 123",
    "city": "Ciudad",
    "state": "Estado",
    "postal_code": "12345",
    "country": "País"
  },
  "billing_address": {
    "street_address": "Calle Principal 123",
    "city": "Ciudad",
    "state": "Estado",
    "postal_code": "12345",
    "country": "País"
  },
  "coupon_code": "DESCUENTO10"
}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "subtotal": 299.97,
    "shipping_cost": 5.99,
    "tax_amount": 30.59,
    "discount_amount": 29.99,
    "total_amount": 306.56,
    "shipping_method": {
      "id": "ship-1",
      "name": "Envío Estándar",
      "description": "Envío estándar de 3-5 días hábiles",
      "cost": 5.99,
      "estimated_days": 5
    },
    "coupon": {
      "id": "coupon-1",
      "code": "DESCUENTO10",
      "discount_type": "percentage",
      "discount_value": 10,
      "discount_amount": 29.99
    },
    "breakdown": {
      "items_total": 299.97,
      "shipping": 5.99,
      "tax": 30.59,
      "discount": -29.99,
      "total": 306.56
    }
  },
  "message": "Cálculo realizado correctamente"
}
```

### 3. Confirmar Pedido
**POST** `/api/v1/checkout/confirm`

Confirma y crea el pedido final.

#### Headers requeridos:
```
Authorization: Bearer {token}
Content-Type: application/json
```

#### Body:
```json
{
  "shipping_method_id": "ship-1",
  "payment_method_id": "pay-1",
  "shipping_address": {
    "street_address": "Calle Principal 123",
    "city": "Ciudad",
    "state": "Estado",
    "postal_code": "12345",
    "country": "País"
  },
  "billing_address": {
    "street_address": "Calle Principal 123",
    "city": "Ciudad",
    "state": "Estado",
    "postal_code": "12345",
    "country": "País"
  },
  "coupon_code": "DESCUENTO10",
  "notes": "Entregar después de las 6 PM"
}
```

#### Respuesta exitosa (201):
```json
{
  "success": true,
  "data": {
    "order": {
      "id": "order-1",
      "order_number": "ORD-20250813-ABC123",
      "status": "pending",
      "total_amount": 306.56,
      "created_at": "2025-08-13T00:51:10.000000Z"
    },
    "summary": {
      "subtotal": 299.97,
      "shipping_cost": 5.99,
      "tax_amount": 30.59,
      "discount_amount": 29.99,
      "total_amount": 306.56
    }
  },
  "message": "Pedido creado correctamente"
}
```

### 4. Métodos de Envío
**GET** `/api/v1/checkout/shipping-methods`

Obtiene todos los métodos de envío disponibles.

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": [
    {
      "id": "ship-1",
      "name": "Envío Estándar",
      "description": "Envío estándar de 3-5 días hábiles",
      "cost": 5.99,
      "estimated_days": 5,
      "is_active": true
    },
    {
      "id": "ship-2",
      "name": "Envío Express",
      "description": "Envío express de 1-2 días hábiles",
      "cost": 12.99,
      "estimated_days": 2,
      "is_active": true
    },
    {
      "id": "ship-3",
      "name": "Envío Gratis",
      "description": "Envío gratis en pedidos superiores a $50",
      "cost": 0.00,
      "estimated_days": 7,
      "is_active": true
    }
  ],
  "message": "Métodos de envío obtenidos correctamente"
}
```

### 5. Métodos de Pago
**GET** `/api/v1/checkout/payment-methods`

Obtiene todos los métodos de pago disponibles.

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": [
    {
      "id": "pay-1",
      "name": "Tarjeta de Crédito",
      "type": "credit_card",
      "is_active": true,
      "configuration": {
        "processor": "stripe",
        "supported_cards": ["visa", "mastercard", "amex"],
        "requires_cvv": true
      }
    },
    {
      "id": "pay-2",
      "name": "PayPal",
      "type": "paypal",
      "is_active": true,
      "configuration": {
        "processor": "paypal",
        "environment": "sandbox"
      }
    },
    {
      "id": "pay-3",
      "name": "Efectivo contra Entrega",
      "type": "cash_on_delivery",
      "is_active": true,
      "configuration": {
        "requires_change": true,
        "max_amount": 1000.00
      }
    }
  ],
  "message": "Métodos de pago obtenidos correctamente"
}
```

### 6. Validar Cupón
**POST** `/api/v1/checkout/validate-coupon`

Valida un código de cupón y calcula el descuento.

#### Headers requeridos:
```
Authorization: Bearer {token}
Content-Type: application/json
```

#### Body:
```json
{
  "coupon_code": "DESCUENTO10",
  "subtotal": 299.97
}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "coupon": {
      "id": "coupon-1",
      "code": "DESCUENTO10",
      "name": "Descuento 10%",
      "description": "10% de descuento en toda la compra",
      "discount_type": "percentage",
      "discount_value": 10,
      "discount_amount": 29.99,
      "minimum_amount": 50.00
    }
  },
  "message": "Cupón válido"
}
```

## Endpoints de Pedidos

### 1. Listar Pedidos
**GET** `/api/v1/orders`

Obtiene todos los pedidos del usuario autenticado.

#### Parámetros de consulta:
- `page` (integer): Número de página (default: 1)
- `per_page` (integer): Elementos por página (default: 10)

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "order-1",
        "order_number": "ORD-20250813-ABC123",
        "status": "pending",
        "payment_status": "pending",
        "total_amount": 306.56,
        "currency": "USD",
        "created_at": "2025-08-13T00:51:10.000000Z",
        "items_count": 3,
        "items": [
          {
            "id": "item-1",
            "product_name": "iPhone 15 Pro",
            "product_sku": "IPH15PRO-256",
            "quantity": 1,
            "unit_price": 999.99,
            "total_price": 999.99,
            "product": {
              "id": "prod-1",
              "name": "iPhone 15 Pro",
              "slug": "iphone-15-pro",
              "image": "https://example.com/iphone.jpg"
            }
          }
        ]
      }
    ],
    "total": 1,
    "per_page": 10,
    "last_page": 1
  },
  "message": "Pedidos obtenidos correctamente"
}
```

### 2. Ver Pedido
**GET** `/api/v1/orders/{id}`

Obtiene los detalles de un pedido específico.

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "id": "order-1",
    "order_number": "ORD-20250813-ABC123",
    "status": "pending",
    "payment_status": "pending",
    "subtotal": 299.97,
    "tax_amount": 30.59,
    "shipping_cost": 5.99,
    "discount_amount": 29.99,
    "total_amount": 306.56,
    "currency": "USD",
    "shipping_address": {
      "street_address": "Calle Principal 123",
      "city": "Ciudad",
      "state": "Estado",
      "postal_code": "12345",
      "country": "País"
    },
    "billing_address": {
      "street_address": "Calle Principal 123",
      "city": "Ciudad",
      "state": "Estado",
      "postal_code": "12345",
      "country": "País"
    },
    "notes": "Entregar después de las 6 PM",
    "created_at": "2025-08-13T00:51:10.000000Z",
    "updated_at": "2025-08-13T00:51:10.000000Z",
    "items": [
      {
        "id": "item-1",
        "product_name": "iPhone 15 Pro",
        "product_sku": "IPH15PRO-256",
        "quantity": 1,
        "unit_price": 999.99,
        "total_price": 999.99,
        "product": {
          "id": "prod-1",
          "name": "iPhone 15 Pro",
          "slug": "iphone-15-pro",
          "image": "https://example.com/iphone.jpg",
          "description": "El iPhone más avanzado"
        }
      }
    ]
  },
  "message": "Pedido obtenido correctamente"
}
```

### 3. Pedidos por Estado
**GET** `/api/v1/orders/status/{status}`

Obtiene pedidos filtrados por estado.

#### Estados válidos:
- `pending`: Pendiente
- `processing`: En procesamiento
- `shipped`: Enviado
- `delivered`: Entregado
- `cancelled`: Cancelado

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "order-1",
        "order_number": "ORD-20250813-ABC123",
        "status": "pending",
        "payment_status": "pending",
        "total_amount": 306.56,
        "currency": "USD",
        "created_at": "2025-08-13T00:51:10.000000Z",
        "items_count": 3
      }
    ],
    "total": 1,
    "per_page": 10,
    "last_page": 1
  },
  "message": "Pedidos con estado 'pending' obtenidos correctamente"
}
```

### 4. Estadísticas de Pedidos
**GET** `/api/v1/orders/stats`

Obtiene estadísticas de pedidos del usuario.

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "total_orders": 5,
    "pending_orders": 1,
    "processing_orders": 1,
    "shipped_orders": 1,
    "delivered_orders": 1,
    "cancelled_orders": 1,
    "total_spent": 1532.80,
    "average_order_value": 306.56
  },
  "message": "Estadísticas de pedidos obtenidas correctamente"
}
```

## Estados de Pedido

| Estado | Descripción |
|--------|-------------|
| `pending` | Pedido creado, pendiente de procesamiento |
| `processing` | Pedido en procesamiento |
| `shipped` | Pedido enviado |
| `delivered` | Pedido entregado |
| `cancelled` | Pedido cancelado |

## Estados de Pago

| Estado | Descripción |
|--------|-------------|
| `pending` | Pago pendiente |
| `paid` | Pago completado |
| `failed` | Pago fallido |
| `refunded` | Pago reembolsado |

## Tipos de Métodos de Pago

| Tipo | Descripción |
|------|-------------|
| `credit_card` | Tarjeta de crédito |
| `debit_card` | Tarjeta de débito |
| `bank_transfer` | Transferencia bancaria |
| `cash_on_delivery` | Efectivo contra entrega |
| `mercadopago` | MercadoPago |
| `paypal` | PayPal |

## Cálculo de Impuestos

Los impuestos se calculan como el 10% del subtotal (implementación simplificada).

## Validaciones

### Cupones
- ✅ Verificar que el cupón esté activo
- ✅ Verificar que no haya expirado
- ✅ Verificar uso máximo por usuario
- ✅ Verificar monto mínimo de compra
- ✅ Calcular descuento (porcentaje o monto fijo)

### Stock
- ✅ Verificar stock disponible antes de iniciar checkout
- ✅ Verificar stock nuevamente antes de confirmar
- ✅ Actualizar stock al crear el pedido

### Direcciones
- ✅ Dirección de envío requerida
- ✅ Dirección de facturación opcional (usa envío si no se proporciona)
- ✅ Validación de campos requeridos

## Ejemplos de Uso

### Flujo Completo de Checkout

1. **Iniciar checkout:**
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/checkout/initiate" \
  -H "Authorization: Bearer {token}"
```

2. **Calcular totales:**
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/checkout/calculate" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "shipping_method_id": "ship-1",
    "shipping_address": {
      "street_address": "Calle Principal 123",
      "city": "Ciudad",
      "state": "Estado",
      "postal_code": "12345",
      "country": "País"
    },
    "coupon_code": "DESCUENTO10"
  }'
```

3. **Confirmar pedido:**
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/checkout/confirm" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "shipping_method_id": "ship-1",
    "payment_method_id": "pay-1",
    "shipping_address": {
      "street_address": "Calle Principal 123",
      "city": "Ciudad",
      "state": "Estado",
      "postal_code": "12345",
      "country": "País"
    },
    "coupon_code": "DESCUENTO10",
    "notes": "Entregar después de las 6 PM"
  }'
```

### Validar Cupón
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/checkout/validate-coupon" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "coupon_code": "DESCUENTO10",
    "subtotal": 299.97
  }'
```

### Obtener Métodos de Envío
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/checkout/shipping-methods" \
  -H "Authorization: Bearer {token}"
```

### Obtener Métodos de Pago
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/checkout/payment-methods" \
  -H "Authorization: Bearer {token}"
```

## Códigos de Estado HTTP

- `200`: Operación exitosa
- `201`: Recurso creado exitosamente
- `400`: Error de validación o datos incorrectos
- `401`: No autenticado
- `404`: Recurso no encontrado
- `405`: Método no permitido
- `500`: Error interno del servidor

## Características Implementadas

### ✅ Checkout Completo
- Inicialización con validación de stock
- Cálculo de totales con impuestos y descuentos
- Confirmación de pedido con transacciones
- Limpieza automática del carrito

### ✅ Gestión de Pedidos
- Listado paginado de pedidos
- Detalles completos de pedidos
- Filtrado por estado
- Estadísticas de usuario

### ✅ Métodos de Envío y Pago
- Configuración flexible de métodos
- Validación de tipos permitidos
- Configuraciones específicas por método

### ✅ Sistema de Cupones
- Validación completa de cupones
- Cálculo de descuentos
- Control de uso máximo por usuario
- Registro de uso de cupones

### ✅ Seguridad y Validaciones
- Verificación de stock en tiempo real
- Validación de direcciones
- Control de acceso por usuario
- Transacciones de base de datos 