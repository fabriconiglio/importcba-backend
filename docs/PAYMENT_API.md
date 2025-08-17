# API de Pagos

## Descripción
API completa para el procesamiento de pagos con soporte para múltiples proveedores (Mock, Stripe) y métodos de pago.

## Arquitectura del Sistema

### Proveedores de Pago
- **Mock Provider**: Para desarrollo y pruebas
- **Stripe Provider**: Para pagos reales (requiere configuración)

### Métodos de Pago Soportados
- Tarjeta de Crédito
- Tarjeta de Débito
- PayPal
- Transferencia Bancaria
- Efectivo contra Entrega
- MercadoPago

## Endpoints

### 1. Procesar Pago
**POST** `/api/v1/payments/process`

Procesa un pago para un pedido específico.

#### Headers requeridos:
```
Authorization: Bearer {token}
Content-Type: application/json
```

#### Body:
```json
{
  "order_id": "order-123",
  "payment_method_id": "pay-1",
  "amount": 299.99,
  "currency": "USD",
  "payment_data": {
    "payment_method_id": "pm_1234567890",
    "card_number": "4242424242424242",
    "cvv": "123",
    "expiry_month": 12,
    "expiry_year": 2025
  }
}
```

#### Respuesta exitosa (201):
```json
{
  "success": true,
  "data": {
    "payment_id": "mock_pay_abc123def456",
    "transaction_id": "mock_txn_xyz789",
    "order_id": "order-123",
    "amount": 299.99,
    "currency": "USD",
    "status": "paid",
    "provider_data": {
      "amount": 299.99,
      "currency": "USD",
      "payment_method": "credit_card",
      "processed_at": "2025-08-13T00:51:10.000000Z",
      "provider": "mock"
    }
  },
  "message": "Pago procesado exitosamente"
}
```

### 2. Obtener Información de Pago
**GET** `/api/v1/payments/info/{paymentId}`

Obtiene información detallada de un pago específico.

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "payment_id": "mock_pay_abc123def456",
    "transaction_id": "mock_txn_xyz789",
    "order_id": "order-123",
    "provider_data": {
      "status": "completed",
      "amount": 299.99,
      "currency": "USD",
      "created_at": "2025-08-13T00:46:10.000000Z",
      "processed_at": "2025-08-13T00:47:10.000000Z",
      "provider": "mock"
    }
  },
  "message": "Información de pago obtenida"
}
```

### 3. Reembolsar Pago
**POST** `/api/v1/payments/refund/{paymentId}`

Procesa un reembolso para un pago específico.

#### Body:
```json
{
  "amount": 150.00,
  "reason": "Cliente solicitó reembolso parcial"
}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "payment_id": "mock_pay_abc123def456",
    "refund_id": "mock_refund_ref789",
    "order_id": "order-123",
    "refund_amount": 150.00,
    "provider_data": {
      "refund_amount": 150.00,
      "refund_id": "mock_refund_ref789",
      "processed_at": "2025-08-13T00:51:10.000000Z",
      "provider": "mock"
    }
  },
  "message": "Reembolso procesado exitosamente"
}
```

### 4. Crear Método de Pago
**POST** `/api/v1/payments/create-method`

Crea un método de pago (para tarjetas).

#### Body:
```json
{
  "payment_method_type": "credit_card",
  "card_number": "4242424242424242",
  "expiry_month": 12,
  "expiry_year": 2025,
  "cvv": "123",
  "cardholder_name": "Juan Pérez",
  "email": "juan@example.com"
}
```

#### Respuesta exitosa (201):
```json
{
  "success": true,
  "data": {
    "payment_method_id": "mock_pm_abc123def456",
    "type": "credit_card",
    "card_info": {
      "brand": "visa",
      "last4": "4242",
      "exp_month": 12,
      "exp_year": 2025
    },
    "provider_data": {
      "payment_method_id": "mock_pm_abc123def456",
      "type": "card",
      "card": {
        "brand": "visa",
        "last4": "4242",
        "exp_month": 12,
        "exp_year": 2025
      },
      "provider": "mock"
    }
  },
  "message": "Método de pago creado exitosamente"
}
```

### 5. Obtener Proveedores Disponibles
**GET** `/api/v1/payments/providers`

Obtiene todos los proveedores de pago disponibles.

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "mock": {
      "name": "mock",
      "class": "App\\Services\\Payment\\MockPaymentProvider",
      "supported_methods": {
        "credit_card": {
          "name": "Tarjeta de Crédito",
          "processors": ["visa", "mastercard", "amex"],
          "requires_cvv": true,
          "requires_expiry": true
        },
        "debit_card": {
          "name": "Tarjeta de Débito",
          "processors": ["visa", "mastercard"],
          "requires_cvv": true,
          "requires_expiry": true
        },
        "paypal": {
          "name": "PayPal",
          "processors": ["paypal"],
          "requires_cvv": false,
          "requires_expiry": false
        }
      }
    }
  },
  "message": "Proveedores de pago obtenidos correctamente"
}
```

### 6. Validar Datos de Pago
**POST** `/api/v1/payments/validate`

Valida los datos de pago antes de procesarlos.

#### Body:
```json
{
  "payment_method_id": "pay-1",
  "payment_data": {
    "card_number": "4242424242424242",
    "cvv": "123",
    "expiry_month": 12,
    "expiry_year": 2025
  }
}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "message": "Datos de pago válidos"
}
```

### 7. Obtener Métodos Soportados por Proveedor
**GET** `/api/v1/payments/providers/{providerName}/methods`

Obtiene los métodos de pago soportados por un proveedor específico.

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "provider": "mock",
    "methods": {
      "credit_card": {
        "name": "Tarjeta de Crédito",
        "processors": ["visa", "mastercard", "amex"],
        "requires_cvv": true,
        "requires_expiry": true
      },
      "debit_card": {
        "name": "Tarjeta de Débito",
        "processors": ["visa", "mastercard"],
        "requires_cvv": true,
        "requires_expiry": true
      }
    }
  },
  "message": "Métodos de pago obtenidos correctamente"
}
```

## Proveedores de Pago

### Mock Provider
- **Propósito**: Desarrollo y pruebas
- **Configuración**: Siempre disponible
- **Tasa de éxito**: 95% (configurable)
- **Métodos soportados**: Todos

#### Configuración:
```php
$config = [
    'success_rate' => 0.95,        // 95% de éxito
    'processing_delay' => 0,       // Sin delay
    'simulate_failures' => false,  // No simular fallos
];
```

### Stripe Provider
- **Propósito**: Pagos reales
- **Configuración**: Requiere API key de Stripe
- **Métodos soportados**: Tarjetas de crédito/débito

#### Configuración:
```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
```

## Estados de Pago

| Estado | Descripción |
|--------|-------------|
| `pending` | Pago pendiente |
| `paid` | Pago completado |
| `failed` | Pago fallido |
| `refunded` | Pago reembolsado |

## Validaciones

### Tarjetas de Crédito/Débito
- ✅ Número de tarjeta válido (13-19 dígitos)
- ✅ CVV válido (3-4 dígitos)
- ✅ Fecha de expiración válida
- ✅ Nombre del titular (opcional)

### PayPal
- ✅ Email válido de PayPal

### Transferencia Bancaria
- ✅ Número de cuenta válido (mínimo 8 dígitos)
- ✅ Número de routing válido (mínimo 8 dígitos)

## Ejemplos de Uso

### Procesar Pago con Tarjeta
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/payments/process" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": "order-123",
    "payment_method_id": "pay-1",
    "amount": 299.99,
    "currency": "USD",
    "payment_data": {
      "payment_method_id": "pm_1234567890",
      "card_number": "4242424242424242",
      "cvv": "123",
      "expiry_month": 12,
      "expiry_year": 2025
    }
  }'
```

### Crear Método de Pago
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/payments/create-method" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "payment_method_type": "credit_card",
    "card_number": "4242424242424242",
    "expiry_month": 12,
    "expiry_year": 2025,
    "cvv": "123",
    "cardholder_name": "Juan Pérez",
    "email": "juan@example.com"
  }'
```

### Reembolsar Pago
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/payments/refund/mock_pay_abc123" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 150.00,
    "reason": "Cliente solicitó reembolso parcial"
  }'
```

### Obtener Proveedores
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/payments/providers" \
  -H "Authorization: Bearer {token}"
```

## Códigos de Error

| Código | Descripción |
|--------|-------------|
| `VALIDATION_ERROR` | Datos de pago inválidos |
| `PAYMENT_DECLINED` | Pago rechazado por el proveedor |
| `PAYMENT_NOT_FOUND` | Pago no encontrado |
| `REQUIRES_ACTION` | Pago requiere confirmación adicional |
| `INTERNAL_ERROR` | Error interno del servidor |

## Códigos de Estado HTTP

- `200`: Operación exitosa
- `201`: Recurso creado exitosamente
- `400`: Error de validación o datos incorrectos
- `401`: No autenticado
- `404`: Recurso no encontrado
- `422`: Error de validación
- `500`: Error interno del servidor

## Características Implementadas

### ✅ Sistema Modular
- Interfaz común para proveedores
- Factory pattern para selección de proveedores
- Fácil extensión para nuevos proveedores

### ✅ Validaciones Completas
- Validación por tipo de método de pago
- Validación de datos de tarjeta
- Validación de montos y monedas

### ✅ Manejo de Errores
- Códigos de error específicos
- Mensajes descriptivos
- Logging de errores

### ✅ Seguridad
- Autenticación requerida
- Validación de propiedad de pedidos
- Sanitización de datos sensibles

### ✅ Flexibilidad
- Múltiples proveedores
- Múltiples métodos de pago
- Configuración por entorno 