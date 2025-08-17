# API de Merge de Carritos

## Descripción
Sistema completo para manejar carritos anónimos y su merge automático con carritos de usuarios autenticados.

## Arquitectura del Sistema

### Carritos Anónimos
- **Identificación**: Por `session_id` en header `X-Session-ID`
- **Expiración**: 7 días por defecto
- **Almacenamiento**: Base de datos con `user_id = null`

### Carritos de Usuario
- **Identificación**: Por `user_id` del usuario autenticado
- **Expiración**: 7 días por defecto
- **Almacenamiento**: Base de datos con `user_id` específico

### Proceso de Merge
1. **Detección**: Al hacer login/registro exitoso
2. **Validación**: Verificar stock y conflictos
3. **Merge**: Combinar items y cantidades
4. **Limpieza**: Eliminar carrito anónimo
5. **Notificación**: Informar resultado al usuario

## Endpoints

### Carrito Anónimo

#### 1. Obtener Carrito Anónimo
**GET** `/api/v1/anonymous-cart`

Obtiene el carrito anónimo por session_id.

#### Headers requeridos:
```
X-Session-ID: {session_id}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "id": "cart-123",
    "items": [
      {
        "id": "item-123",
        "product": {
          "id": "product-123",
          "name": "Producto Ejemplo",
          "slug": "producto-ejemplo",
          "image": "https://example.com/image.jpg",
          "category": "Electrónicos",
          "brand": "Marca Ejemplo"
        },
        "quantity": 2,
        "price": 29.99,
        "original_price": 39.99,
        "subtotal": 59.98,
        "savings": 20.00,
        "has_discount": true,
        "discount_percentage": 25.00
      }
    ],
    "total_items": 2,
    "total": 59.98,
    "total_savings": 20.00
  },
  "message": "Carrito anónimo obtenido correctamente"
}
```

#### 2. Agregar Item al Carrito Anónimo
**POST** `/api/v1/anonymous-cart/add`

Agrega un producto al carrito anónimo.

#### Headers requeridos:
```
X-Session-ID: {session_id}
Content-Type: application/json
```

#### Body:
```json
{
  "product_id": "product-123",
  "quantity": 2
}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "item": {
      "id": "item-123",
      "product": {
        "id": "product-123",
        "name": "Producto Ejemplo",
        "slug": "producto-ejemplo",
        "image": "https://example.com/image.jpg"
      },
      "quantity": 2,
      "price": 29.99,
      "original_price": 39.99,
      "subtotal": 59.98
    },
    "cart_total_items": 2,
    "cart_total": 59.98
  },
  "message": "Producto agregado al carrito anónimo correctamente"
}
```

#### 3. Actualizar Item del Carrito Anónimo
**PUT** `/api/v1/anonymous-cart/items/{itemId}`

Actualiza la cantidad de un item en el carrito anónimo.

#### Headers requeridos:
```
X-Session-ID: {session_id}
Content-Type: application/json
```

#### Body:
```json
{
  "quantity": 3
}
```

#### 4. Remover Item del Carrito Anónimo
**DELETE** `/api/v1/anonymous-cart/items/{itemId}`

Remueve un item del carrito anónimo.

#### Headers requeridos:
```
X-Session-ID: {session_id}
```

#### 5. Limpiar Carrito Anónimo
**DELETE** `/api/v1/anonymous-cart/clear`

Limpia completamente el carrito anónimo.

#### Headers requeridos:
```
X-Session-ID: {session_id}
```

#### 6. Obtener Cantidad de Items
**GET** `/api/v1/anonymous-cart/count`

Obtiene la cantidad total de items en el carrito anónimo.

#### Headers requeridos:
```
X-Session-ID: {session_id}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "count": 3
  }
}
```

#### 7. Obtener Total del Carrito Anónimo
**GET** `/api/v1/anonymous-cart/total`

Obtiene el total y ahorros del carrito anónimo.

#### Headers requeridos:
```
X-Session-ID: {session_id}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "total": 89.97,
    "savings": 30.00
  }
}
```

### Merge de Carritos

#### 1. Merge Manual de Carrito
**POST** `/api/v1/cart-merge/merge`

Mergea manualmente un carrito anónimo con el carrito del usuario.

#### Headers requeridos:
```
Authorization: Bearer {token}
Content-Type: application/json
```

#### Body:
```json
{
  "session_id": "session-123"
}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "merged_items": 3,
    "conflicts": 0,
    "conflict_details": [],
    "user_cart_id": "user-cart-456"
  },
  "message": "Carrito mergeado correctamente"
}
```

#### 2. Obtener Información del Carrito Anónimo
**GET** `/api/v1/cart-merge/anonymous-info`

Obtiene información sobre un carrito anónimo específico.

#### Headers requeridos:
```
Authorization: Bearer {token}
```

#### Query Parameters:
```
session_id: {session_id}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "exists": true,
    "cart_id": "cart-123",
    "total_items": 3,
    "total": 89.97,
    "total_savings": 30.00,
    "expires_at": "2025-08-20T01:00:00.000000Z"
  },
  "message": "Información del carrito anónimo obtenida"
}
```

#### 3. Estadísticas de Carritos Anónimos (Admin)
**GET** `/api/v1/cart-merge/stats`

Obtiene estadísticas de carritos anónimos (solo administradores).

#### Headers requeridos:
```
Authorization: Bearer {token}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "total": 150,
    "active": 120,
    "expired": 30
  },
  "message": "Estadísticas obtenidas correctamente"
}
```

#### 4. Limpiar Carritos Expirados (Admin)
**POST** `/api/v1/cart-merge/clean-expired`

Limpia carritos anónimos expirados (solo administradores).

#### Headers requeridos:
```
Authorization: Bearer {token}
```

#### Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "deleted_carts": 30
  },
  "message": "Se eliminaron 30 carritos anónimos expirados"
}
```

## Proceso de Merge Automático

### Flujo de Login/Registro
1. **Usuario hace login/registro** → Middleware detecta la ruta
2. **Verificar carrito anónimo** → Buscar por session_id
3. **Validar items** → Verificar stock y conflictos
4. **Realizar merge** → Combinar carritos
5. **Limpiar carrito anónimo** → Eliminar después del merge
6. **Notificar resultado** → Agregar info a la respuesta

### Respuesta de Login con Merge
```json
{
  "success": true,
  "data": {
    "user": {
      "id": "user-123",
      "name": "Juan Pérez",
      "email": "juan@example.com"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  },
  "cart_merge": {
    "merged_items": 3,
    "conflicts": 0,
    "message": "Carrito mergeado correctamente"
  }
}
```

## Lógica de Merge

### Reglas de Combinación
1. **Producto no existe en carrito usuario** → Agregar como nuevo item
2. **Producto ya existe** → Sumar cantidades
3. **Stock insuficiente** → Marcar como conflicto
4. **Precios diferentes** → Usar el precio más bajo
5. **Descuentos** → Mantener el descuento más alto

### Validaciones
- ✅ **Stock disponible** para cantidades totales
- ✅ **Producto activo** y disponible
- ✅ **Precios válidos** y actualizados
- ✅ **Cantidades positivas** y razonables

### Manejo de Conflictos
- **Stock insuficiente**: No se mergea el item
- **Producto no encontrado**: Se ignora el item
- **Precio inválido**: Se usa precio actual del producto

## Ejemplos de Uso

### Frontend - Generar Session ID
```javascript
// Generar session ID único
const sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

// Guardar en localStorage
localStorage.setItem('anonymous_session_id', sessionId);

// Usar en headers
const headers = {
  'X-Session-ID': sessionId,
  'Content-Type': 'application/json'
};
```

### Frontend - Agregar al Carrito Anónimo
```javascript
const addToAnonymousCart = async (productId, quantity) => {
  const sessionId = localStorage.getItem('anonymous_session_id');
  
  const response = await fetch('/api/v1/anonymous-cart/add', {
    method: 'POST',
    headers: {
      'X-Session-ID': sessionId,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      product_id: productId,
      quantity: quantity
    })
  });
  
  return response.json();
};
```

### Frontend - Login con Merge
```javascript
const login = async (email, password) => {
  const sessionId = localStorage.getItem('anonymous_session_id');
  
  const response = await fetch('/api/v1/login', {
    method: 'POST',
    headers: {
      'X-Session-ID': sessionId,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      email: email,
      password: password
    })
  });
  
  const result = await response.json();
  
  if (result.success && result.cart_merge) {
    // Mostrar notificación de merge
    showNotification(`Se agregaron ${result.cart_merge.merged_items} productos a tu carrito`);
    
    // Limpiar session ID anónimo
    localStorage.removeItem('anonymous_session_id');
  }
  
  return result;
};
```

### Frontend - Merge Manual
```javascript
const mergeCart = async () => {
  const sessionId = localStorage.getItem('anonymous_session_id');
  const token = localStorage.getItem('auth_token');
  
  const response = await fetch('/api/v1/cart-merge/merge', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      session_id: sessionId
    })
  });
  
  const result = await response.json();
  
  if (result.success) {
    // Limpiar session ID anónimo
    localStorage.removeItem('anonymous_session_id');
    
    // Actualizar carrito del usuario
    updateUserCart();
  }
  
  return result;
};
```

## Códigos de Error

| Error | Descripción |
|-------|-------------|
| `Session ID requerido` | Falta el header X-Session-ID |
| `Carrito anónimo no encontrado` | No existe carrito para el session_id |
| `Stock insuficiente` | No hay suficiente stock para el merge |
| `Producto no encontrado` | El producto ya no existe |
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

### ✅ Sistema Completo de Carritos Anónimos
- Creación automática por session_id
- Gestión completa de items (agregar, actualizar, remover)
- Cálculo de totales y descuentos
- Expiración automática

### ✅ Merge Automático
- Detección automática en login/registro
- Validación de stock y conflictos
- Combinación inteligente de items
- Limpieza automática de carritos anónimos

### ✅ Validaciones Robustas
- Verificación de stock disponible
- Validación de productos activos
- Manejo de precios y descuentos
- Prevención de conflictos

### ✅ Seguridad
- Autenticación requerida para merge
- Validación de propiedad de carritos
- Limpieza automática de datos expirados
- Logging de errores y operaciones

### ✅ Flexibilidad
- Merge automático y manual
- Configuración de expiración
- Estadísticas para administradores
- API completa para frontend

### ✅ Integración
- Middleware automático
- Compatible con sistema de autenticación
- Integración con carrito de usuario
- Notificaciones de merge

## Próximas Mejoras

- [ ] Merge por categorías de productos
- [ ] Merge con cupones aplicados
- [ ] Merge con direcciones guardadas
- [ ] Notificaciones push de merge
- [ ] Historial de merges por usuario
- [ ] Merge con preferencias de usuario
- [ ] Merge con historial de compras
- [ ] Merge con lista de deseos 