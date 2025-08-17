# API de Reservas de Stock

## Descripción
Sistema completo para manejar reservas de stock con consistencia y control de inventario en tiempo real.

## Arquitectura del Sistema

### Reservas de Stock
- **Identificación**: UUID único para cada reserva
- **Expiración**: Configurable (por defecto 30 minutos)
- **Estados**: `pending`, `confirmed`, `cancelled`, `expired`
- **Consistencia**: Transacciones de base de datos para garantizar integridad

### Flujo de Reserva
1. **Verificación**: Comprobar stock disponible considerando reservas activas
2. **Creación**: Crear reserva con estado `pending`
3. **Confirmación**: Al confirmar pedido, cambiar a `confirmed` y ajustar stock
4. **Limpieza**: Reservas expiradas se marcan como `expired`

### Integración con Checkout
- **Pre-checkout**: Verificar stock disponible
- **Durante checkout**: Crear reservas temporales
- **Confirmación**: Confirmar reservas y ajustar stock real
- **Cancelación**: Cancelar reservas si es necesario

## Endpoints

### Gestión de Reservas

#### 1. Crear Reserva de Stock
**POST** `/api/v1/stock-reservations`

Crea una nueva reserva de stock.

#### Body:
```json
{
  "product_id": "product-123",
  "quantity": 2,
  "order_id": "order-456",
  "user_id": "user-789",
  "session_id": "session-abc",
  "expiration_minutes": 30,
  "metadata": {
    "source": "checkout",
    "notes": "Reserva para pedido"
  }
}
```

#### Respuesta exitosa (201):
```json
{
  "success": true,
  "message": "Reserva creada correctamente",
  "data": {
    "reservation_id": "reservation-123",
    "product_id": "product-123",
    "quantity": 2,
    "expires_at": "2025-08-13T02:30:00.000000Z",
    "available_stock": 15
  }
}
```

#### 2. Confirmar Reserva
**POST** `/api/v1/stock-reservations/{reservationId}/confirm`

Confirma una reserva y ajusta el stock del producto.

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "message": "Reserva confirmada y stock ajustado correctamente",
  "data": {
    "reservation_id": "reservation-123",
    "product_id": "product-123",
    "quantity": 2,
    "new_stock": 13
  }
}
```

#### 3. Cancelar Reserva
**POST** `/api/v1/stock-reservations/{reservationId}/cancel`

Cancela una reserva sin afectar el stock.

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "message": "Reserva cancelada correctamente",
  "data": {
    "reservation_id": "reservation-123",
    "available_stock": 15
  }
}
```

#### 4. Extender Expiración
**POST** `/api/v1/stock-reservations/{reservationId}/extend`

Extiende el tiempo de expiración de una reserva.

#### Body:
```json
{
  "minutes": 60
}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "reservation_id": "reservation-123",
    "new_expires_at": "2025-08-13T03:00:00.000000Z"
  },
  "message": "Expiración extendida correctamente"
}
```

### Reservas por Pedido

#### 5. Reservar Stock para Pedido Completo
**POST** `/api/v1/stock-reservations/order/{orderId}/reserve`

Crea reservas para todos los items de un pedido.

#### Body:
```json
{
  "expiration_minutes": 30
}
```

#### Respuesta exitosa (201):
```json
{
  "success": true,
  "message": "Stock reservado correctamente para el pedido",
  "data": {
    "order_id": "order-456",
    "reservations": [
      {
        "reservation_id": "reservation-123",
        "product_id": "product-123",
        "quantity": 2,
        "expires_at": "2025-08-13T02:30:00.000000Z"
      }
    ],
    "total_reservations": 1
  }
}
```

#### 6. Confirmar Reservas de Pedido
**POST** `/api/v1/stock-reservations/order/{orderId}/confirm`

Confirma todas las reservas de un pedido.

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "message": "Se confirmaron 2 reservas correctamente",
  "data": {
    "order_id": "order-456",
    "confirmed_reservations": 2
  }
}
```

#### 7. Cancelar Reservas de Pedido
**POST** `/api/v1/stock-reservations/order/{orderId}/cancel`

Cancela todas las reservas de un pedido.

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "message": "Se cancelaron 2 reservas",
  "data": {
    "order_id": "order-456",
    "cancelled_reservations": 2
  }
}
```

### Consultas de Stock

#### 8. Obtener Stock Disponible
**GET** `/api/v1/stock-reservations/product/{productId}/available`

Obtiene el stock disponible de un producto considerando reservas.

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "product_id": "product-123",
    "product_name": "Producto Ejemplo",
    "total_stock": 20,
    "reserved_quantity": 5,
    "available_stock": 15
  },
  "message": "Stock disponible obtenido correctamente"
}
```

#### 9. Verificar Disponibilidad
**POST** `/api/v1/stock-reservations/check-availability`

Verifica si hay stock disponible para una cantidad específica.

#### Body:
```json
{
  "product_id": "product-123",
  "quantity": 3
}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "product_id": "product-123",
    "product_name": "Producto Ejemplo",
    "requested_quantity": 3,
    "total_stock": 20,
    "available_stock": 15,
    "has_available_stock": true,
    "can_reserve": true
  },
  "message": "Stock disponible"
}
```

#### 10. Obtener Reservas de Producto
**GET** `/api/v1/stock-reservations/product/{productId}/reservations`

Obtiene todas las reservas de un producto específico.

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "product_id": "product-123",
    "reservations": [
      {
        "id": "reservation-123",
        "quantity": 2,
        "status": "pending",
        "expires_at": "2025-08-13T02:30:00.000000Z",
        "order_id": "order-456",
        "user_id": "user-789",
        "created_at": "2025-08-13T02:00:00.000000Z"
      }
    ],
    "total_reservations": 1,
    "active_reservations": 1
  }
}
```

### Administración (Solo Admin)

#### 11. Estadísticas de Reservas
**GET** `/api/v1/stock-reservations/stats`

Obtiene estadísticas generales de reservas.

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "total": 150,
    "active": 120,
    "expired": 20,
    "confirmed": 8,
    "cancelled": 2
  },
  "message": "Estadísticas obtenidas correctamente"
}
```

#### 12. Limpiar Reservas Expiradas
**POST** `/api/v1/stock-reservations/clean-expired`

Limpia todas las reservas expiradas.

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "message": "Se limpiaron 20 reservas expiradas",
  "data": {
    "cleaned_reservations": 20
  }
}
```

## Integración con Checkout

### Flujo Completo de Checkout con Reservas

#### 1. Iniciar Checkout
```javascript
const initiateCheckout = async () => {
  const response = await fetch('/api/v1/checkout/initiate', {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const result = await response.json();
  
  if (result.success) {
    // El sistema ya verificó stock disponible considerando reservas
    console.log('Stock verificado:', result.data.cart_summary);
  }
};
```

#### 2. Confirmar Pedido
```javascript
const confirmOrder = async (orderData) => {
  const response = await fetch('/api/v1/checkout/confirm', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(orderData)
  });
  
  const result = await response.json();
  
  if (result.success) {
    // El sistema creó reservas automáticamente
    console.log('Pedido confirmado:', result.data.order);
  }
};
```

#### 3. Confirmar Reservas (Opcional)
```javascript
const confirmReservations = async (orderId) => {
  const response = await fetch(`/api/v1/stock-reservations/order/${orderId}/confirm`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const result = await response.json();
  
  if (result.success) {
    console.log('Reservas confirmadas:', result.data.confirmed_reservations);
  }
};
```

## Comandos Artisan

### Limpiar Reservas Expiradas
```bash
# Limpiar reservas expiradas
php artisan stock:clean-expired

# Modo dry-run (ver qué se limpiaría sin ejecutar)
php artisan stock:clean-expired --dry-run
```

### Programar Limpieza Automática
Agregar al cron del servidor:
```bash
# Cada 5 minutos
*/5 * * * * cd /path/to/project && php artisan stock:clean-expired
```

## Lógica de Negocio

### Cálculo de Stock Disponible
```php
// Stock disponible = Stock total - Reservas activas
$availableStock = $product->stock_quantity - $activeReservations->sum('quantity');
```

### Estados de Reserva
- **`pending`**: Reserva activa, stock reservado
- **`confirmed`**: Reserva confirmada, stock ajustado
- **`cancelled`**: Reserva cancelada, stock liberado
- **`expired`**: Reserva expirada, stock liberado

### Reglas de Validación
- ✅ **Stock suficiente**: Verificar disponibilidad antes de crear reserva
- ✅ **Expiración**: Reservas expiran automáticamente
- ✅ **Consistencia**: Transacciones para evitar condiciones de carrera
- ✅ **Limpieza**: Proceso automático de limpieza de expiradas

### Manejo de Errores
- **Stock insuficiente**: Error 400 con detalles
- **Reserva no encontrada**: Error 404
- **Reserva expirada**: Error 400
- **Acceso denegado**: Error 403 para operaciones admin

## Ejemplos de Uso

### Frontend - Verificar Stock Antes de Agregar al Carrito
```javascript
const checkStockBeforeAdd = async (productId, quantity) => {
  const response = await fetch('/api/v1/stock-reservations/check-availability', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      product_id: productId,
      quantity: quantity
    })
  });
  
  const result = await response.json();
  
  if (result.success && result.data.has_available_stock) {
    // Agregar al carrito
    addToCart(productId, quantity);
  } else {
    showError('Stock insuficiente');
  }
};
```

### Frontend - Mostrar Stock Disponible
```javascript
const displayAvailableStock = async (productId) => {
  const response = await fetch(`/api/v1/stock-reservations/product/${productId}/available`);
  const result = await response.json();
  
  if (result.success) {
    const { available_stock, total_stock, reserved_quantity } = result.data;
    
    document.getElementById('stock-display').innerHTML = `
      <span class="available">${available_stock} disponibles</span>
      <span class="total">de ${total_stock} total</span>
      ${reserved_quantity > 0 ? `<span class="reserved">(${reserved_quantity} reservados)</span>` : ''}
    `;
  }
};
```

### Backend - Crear Reserva Temporal
```php
// En el controlador de carrito
public function addToCart(Request $request)
{
    $productId = $request->product_id;
    $quantity = $request->quantity;
    
    // Verificar stock disponible
    if (!$this->stockReservationService->hasAvailableStock($productId, $quantity)) {
        return response()->json([
            'success' => false,
            'message' => 'Stock insuficiente'
        ], 400);
    }
    
    // Crear reserva temporal (opcional)
    $this->stockReservationService->createReservation(
        $productId,
        $quantity,
        null, // order_id
        $request->user()->id,
        $request->header('X-Session-ID'),
        5 // 5 minutos de expiración
    );
    
    // Agregar al carrito
    // ...
}
```

## Códigos de Error

| Error | Descripción |
|-------|-------------|
| `Stock insuficiente` | No hay suficiente stock disponible |
| `Reserva no encontrada` | La reserva especificada no existe |
| `La reserva no está activa` | La reserva está expirada o cancelada |
| `Producto no encontrado` | El producto especificado no existe |
| `Acceso denegado` | Solo administradores pueden acceder |

## Códigos de Estado HTTP

- `200`: Operación exitosa
- `201`: Recurso creado exitosamente
- `400`: Error de validación o datos incorrectos
- `401`: No autenticado
- `403`: Acceso denegado (solo admin)
- `404`: Recurso no encontrado
- `422`: Error de validación
- `500`: Error interno del servidor

## Características Implementadas

### ✅ Sistema Completo de Reservas
- Creación, confirmación y cancelación de reservas
- Expiración automática configurable
- Estados de reserva bien definidos
- Metadatos para información adicional

### ✅ Integración con Checkout
- Verificación de stock en tiempo real
- Creación automática de reservas al confirmar pedido
- Confirmación de reservas con ajuste de stock
- Cancelación de reservas si es necesario

### ✅ Consistencia de Datos
- Transacciones de base de datos
- Verificación de stock antes de reservar
- Prevención de condiciones de carrera
- Rollback automático en caso de error

### ✅ Administración
- Estadísticas de reservas
- Limpieza automática de expiradas
- Comando Artisan para mantenimiento
- Logs detallados de operaciones

### ✅ API Completa
- Endpoints para todas las operaciones
- Validación robusta de datos
- Respuestas consistentes
- Documentación completa

### ✅ Flexibilidad
- Expiración configurable por reserva
- Metadatos personalizables
- Integración con carrito y pedidos
- Compatible con sistema existente

## Próximas Mejoras

- [ ] Notificaciones de reservas expiradas
- [ ] Reservas por lotes para múltiples productos
- [ ] Historial de cambios de estado
- [ ] Reservas con prioridad
- [ ] Integración con sistema de alertas
- [ ] Dashboard de reservas en tiempo real
- [ ] Reservas con condiciones especiales
- [ ] Sistema de cola para reservas 