# API de Cupones y Descuentos

## Descripción
API completa para el manejo de cupones de descuento con validaciones por fecha, uso mínimo y límites de uso.

## Características del Sistema

### Tipos de Cupones
- **percentage**: Descuento porcentual (ej: 10%, 20%)
- **fixed_amount**: Descuento de monto fijo (ej: $15, $25)

### Validaciones Implementadas
- ✅ **Fechas**: Inicio y expiración
- ✅ **Monto mínimo**: Requisito de compra mínima
- ✅ **Límite de uso global**: Máximo número de usos totales
- ✅ **Uso por usuario**: Un cupón por usuario
- ✅ **Estado activo**: Solo cupones habilitados
- ✅ **Cálculo automático**: Descuentos calculados automáticamente

## Endpoints

### 1. Listar Cupones Activos
**GET** `/api/v1/coupons`

Obtiene todos los cupones activos disponibles para el usuario.

#### Headers requeridos:
```
Authorization: Bearer {token}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "coupon-123",
        "code": "DESCUENTO10",
        "name": "Descuento 10%",
        "description": "10% de descuento en toda la compra",
        "type": "percentage",
        "value": 10.00,
        "minimum_amount": 50.00,
        "usage_limit": 100,
        "used_count": 15,
        "remaining_uses": 85,
        "starts_at": "2025-08-13T01:00:56.000000Z",
        "expires_at": "2025-11-13T01:00:56.000000Z",
        "is_available": true,
        "user_usage_count": 0
      }
    ],
    "per_page": 10,
    "total": 8
  },
  "message": "Cupones obtenidos correctamente"
}
```

### 2. Validar Cupón
**POST** `/api/v1/coupons/validate`

Valida un cupón específico y calcula el descuento.

#### Headers requeridos:
```
Authorization: Bearer {token}
Content-Type: application/json
```

#### Body:
```json
{
  "code": "DESCUENTO10",
  "subtotal": 299.99
}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "coupon": {
      "id": "coupon-123",
      "code": "DESCUENTO10",
      "name": "Descuento 10%",
      "description": "10% de descuento en toda la compra",
      "type": "percentage",
      "value": 10.00,
      "minimum_amount": 50.00,
      "discount_amount": 29.99,
      "subtotal": 299.99,
      "final_amount": 270.00
    }
  },
  "message": "Cupón válido"
}
```

#### Respuesta de error (400):
```json
{
  "success": false,
  "message": "El monto mínimo para usar este cupón es $50.00"
}
```

### 3. Aplicar Cupón a Pedido
**POST** `/api/v1/coupons/apply`

Aplica un cupón a un pedido específico.

#### Headers requeridos:
```
Authorization: Bearer {token}
Content-Type: application/json
```

#### Body:
```json
{
  "code": "DESCUENTO10",
  "order_id": "order-123"
}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "order": {
      "id": "order-123",
      "subtotal": 299.99,
      "discount_amount": 29.99,
      "total_amount": 270.00
    },
    "coupon": {
      "id": "coupon-123",
      "code": "DESCUENTO10",
      "name": "Descuento 10%",
      "type": "percentage",
      "value": 10.00
    }
  },
  "message": "Cupón aplicado correctamente"
}
```

### 4. Remover Cupón de Pedido
**POST** `/api/v1/coupons/remove`

Remueve un cupón aplicado a un pedido.

#### Headers requeridos:
```
Authorization: Bearer {token}
Content-Type: application/json
```

#### Body:
```json
{
  "order_id": "order-123"
}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "order": {
      "id": "order-123",
      "subtotal": 299.99,
      "discount_amount": 0,
      "total_amount": 299.99
    }
  },
  "message": "Cupón removido correctamente"
}
```

### 5. Historial de Cupones
**GET** `/api/v1/coupons/history`

Obtiene el historial de cupones utilizados por el usuario.

#### Headers requeridos:
```
Authorization: Bearer {token}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "usage-123",
        "coupon": {
          "id": "coupon-123",
          "code": "DESCUENTO10",
          "name": "Descuento 10%",
          "type": "percentage",
          "value": 10.00
        },
        "order": {
          "id": "order-123",
          "order_number": "ORD-001",
          "total_amount": 270.00
        },
        "discount_amount": 29.99,
        "used_at": "2025-08-13T01:30:00.000000Z"
      }
    ],
    "per_page": 10,
    "total": 1
  },
  "message": "Historial de cupones obtenido correctamente"
}
```

## Cupones de Prueba Disponibles

### Cupones Activos
| Código | Nombre | Tipo | Valor | Mínimo | Límite |
|--------|--------|------|-------|--------|--------|
| `DESCUENTO10` | Descuento 10% | percentage | 10% | $50 | 100 |
| `DESCUENTO20` | Descuento 20% | percentage | 20% | $100 | 50 |
| `ENVIOGRATIS` | Envío Gratis | fixed_amount | $5.99 | $25 | 200 |
| `BIENVENIDA` | Cupón de Bienvenida | fixed_amount | $15 | $30 | 1000 |
| `FLASH` | Oferta Flash | fixed_amount | $25 | $100 | 100 |
| `SINLIMITE` | Sin Límite de Uso | fixed_amount | $10 | $25 | ∞ |

### Cupones para Pruebas
| Código | Nombre | Estado | Propósito |
|--------|--------|--------|-----------|
| `EXPIRADO` | Cupón Expirado | Expirado | Pruebas de expiración |
| `FUTURO` | Cupón Futuro | No disponible | Pruebas de fecha futura |
| `BLACKFRIDAY` | Black Friday | Futuro | Pruebas de fechas específicas |
| `NAVIDAD` | Cupón de Navidad | Futuro | Pruebas de fechas específicas |

## Validaciones del Sistema

### Validaciones de Fecha
- ✅ **Fecha de inicio**: Cupón no disponible antes de `starts_at`
- ✅ **Fecha de expiración**: Cupón no válido después de `expires_at`
- ✅ **Zona horaria**: Todas las fechas en UTC

### Validaciones de Uso
- ✅ **Límite global**: No exceder `usage_limit`
- ✅ **Uso por usuario**: Máximo 1 uso por usuario
- ✅ **Estado activo**: Solo cupones con `is_active = true`

### Validaciones de Monto
- ✅ **Monto mínimo**: Subtotal debe ser ≥ `minimum_amount`
- ✅ **Cálculo correcto**: Descuento calculado automáticamente
- ✅ **Límite de descuento**: No exceder el subtotal

### Validaciones de Pedido
- ✅ **Propiedad**: Solo pedidos del usuario autenticado
- ✅ **Estado**: No aplicar a pedidos ya pagados
- ✅ **Cupón único**: Un cupón por pedido

## Cálculo de Descuentos

### Descuento Porcentual
```php
// Ejemplo: 10% de descuento en $299.99
$subtotal = 299.99;
$percentage = 10;
$discount = ($subtotal * $percentage) / 100; // 29.99
$final = $subtotal - $discount; // 270.00
```

### Descuento de Monto Fijo
```php
// Ejemplo: $15 de descuento en $299.99
$subtotal = 299.99;
$fixed_discount = 15.00;
$discount = min($fixed_discount, $subtotal); // 15.00
$final = $subtotal - $discount; // 284.99
```

## Ejemplos de Uso

### Validar Cupón
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/coupons/validate" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "DESCUENTO10",
    "subtotal": 299.99
  }'
```

### Aplicar Cupón
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/coupons/apply" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "DESCUENTO10",
    "order_id": "order-123"
  }'
```

### Remover Cupón
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/coupons/remove" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": "order-123"
  }'
```

### Listar Cupones
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/coupons" \
  -H "Authorization: Bearer {token}"
```

### Historial de Cupones
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/coupons/history" \
  -H "Authorization: Bearer {token}"
```

## Códigos de Error

| Error | Descripción |
|-------|-------------|
| `Cupón no válido o inactivo` | Código incorrecto o cupón deshabilitado |
| `Este cupón aún no está disponible` | Fecha de inicio futura |
| `Este cupón ha expirado` | Fecha de expiración pasada |
| `Este cupón ya no está disponible (límite de uso alcanzado)` | Límite global excedido |
| `El monto mínimo para usar este cupón es $X.XX` | Subtotal insuficiente |
| `Ya has usado este cupón` | Uso previo por el usuario |
| `No se puede aplicar cupón a un pedido ya pagado` | Pedido en estado incorrecto |
| `El pedido ya tiene un cupón aplicado` | Cupón ya aplicado |

## Códigos de Estado HTTP

- `200`: Operación exitosa
- `201`: Recurso creado exitosamente
- `400`: Error de validación o datos incorrectos
- `401`: No autenticado
- `404`: Recurso no encontrado
- `422`: Error de validación
- `500`: Error interno del servidor

## Características Implementadas

### ✅ Sistema Completo de Validaciones
- Validación por fechas (inicio/expiración)
- Validación de monto mínimo
- Validación de límites de uso
- Validación de uso por usuario
- Validación de estado activo

### ✅ Gestión de Pedidos
- Aplicar cupón a pedido
- Remover cupón de pedido
- Actualización automática de totales
- Registro de uso de cupones

### ✅ Cálculo Automático
- Descuentos porcentuales
- Descuentos de monto fijo
- Límite de descuento (no exceder subtotal)
- Actualización de totales del pedido

### ✅ Seguridad
- Autenticación requerida
- Validación de propiedad de pedidos
- Prevención de uso múltiple
- Validación de estados de pedido

### ✅ Historial y Auditoría
- Registro de uso de cupones
- Historial por usuario
- Contadores de uso automáticos
- Trazabilidad completa

### ✅ Flexibilidad
- Múltiples tipos de cupón
- Configuración por cupón
- Fechas personalizables
- Límites configurables

## Integración con Checkout

El sistema de cupones se integra perfectamente con el sistema de checkout:

1. **Validación**: Antes de aplicar el cupón
2. **Cálculo**: Durante el proceso de checkout
3. **Aplicación**: Al confirmar el pedido
4. **Registro**: En la tabla de usos
5. **Actualización**: De totales del pedido

## Próximas Mejoras

- [ ] Cupones por categoría de producto
- [ ] Cupones por marca
- [ ] Cupones de primer compra
- [ ] Cupones de cumpleaños
- [ ] Sistema de referidos
- [ ] Cupones combinables
- [ ] Descuentos por volumen
- [ ] Cupones de fidelización 