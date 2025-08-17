# Checkout Integration - Frontend + Backend

## Descripción

Integración completa del flujo de checkout entre el frontend Next.js y el backend Laravel, incluyendo autenticación, gestión de estados y manejo de errores.

## Componentes Implementados

### 1. **API Client** (`lib/api.ts`)

**Interfaces:**
```typescript
interface CheckoutData {
  cart_summary: {
    total_items: number
    subtotal: number
    shipping_cost: number
    tax_amount: number
    discount_amount: number
    total_amount: number
  }
  addresses: Address[]
  shipping_methods: ShippingMethod[]
  payment_methods: PaymentMethod[]
  items: Array<{
    id: string
    product: Product
    quantity: number
    price: number
    original_price: number | null
    subtotal: number
    has_discount: boolean
    discount_percentage: number
  }>
}

interface CheckoutRequest {
  shipping_address_id: string
  billing_address_id?: string
  shipping_method_id: string
  payment_method_id: string
  coupon_code?: string
  notes?: string
}
```

**Endpoints:**
- `checkoutApi.initiate()` - Iniciar checkout
- `checkoutApi.calculate()` - Calcular totales
- `checkoutApi.confirm()` - Confirmar pedido
- `checkoutApi.applyCoupon()` - Aplicar cupón
- `checkoutApi.removeCoupon()` - Remover cupón

### 2. **Hook Personalizado** (`lib/hooks/useCheckout.ts`)

**Funcionalidades:**
- ✅ Gestión de estado del checkout
- ✅ Verificación de autenticación
- ✅ Carga automática de datos
- ✅ Selección de valores por defecto
- ✅ Manejo de errores
- ✅ Pre-llenado de información del usuario

**Estados:**
```typescript
interface UseCheckoutReturn {
  checkoutData: CheckoutData | null
  loading: boolean
  error: string | null
  step: 'init' | 'info' | 'payment' | 'confirmation'
  selectedAddress: Address | null
  selectedShippingMethod: ShippingMethod | null
  selectedPaymentMethod: PaymentMethod | null
  customerInfo: CustomerInfo
}
```

### 3. **Página de Checkout** (`app/checkout/page.tsx`)

**Características:**
- ✅ **3 pasos del checkout**: Información → Pago → Confirmación
- ✅ **Autenticación requerida** con redirección automática
- ✅ **Datos dinámicos** desde la API
- ✅ **Estados de carga** y manejo de errores
- ✅ **Validaciones** en tiempo real
- ✅ **Responsive design** completo

## Flujo de Integración

### **1. Inicialización**
```typescript
// El hook verifica autenticación automáticamente
useEffect(() => {
  if (!isAuthenticated) {
    router.push('/login?redirect=/checkout')
    return
  }
  
  if (step === 'init') {
    initiateCheckout()
  }
}, [isAuthenticated, router, step])
```

### **2. Carga de Datos**
```typescript
const initiateCheckout = async () => {
  const response = await checkoutApi.initiate()
  
  if (response.success && response.data) {
    setCheckoutData(response.data)
    
    // Seleccionar valores por defecto
    const defaultAddress = response.data.addresses.find(addr => addr.is_default)
    const defaultShipping = response.data.shipping_methods.find(method => method.is_active)
    const defaultPayment = response.data.payment_methods.find(method => method.is_active)
    
    setSelectedAddress(defaultAddress || null)
    setSelectedShippingMethod(defaultShipping || null)
    setSelectedPaymentMethod(defaultPayment || null)
    
    setStep('info')
  }
}
```

### **3. Cálculo de Totales**
```typescript
const handleInfoSubmit = async (e: React.FormEvent) => {
  e.preventDefault()
  
  if (!selectedAddress || !selectedShippingMethod) {
    alert('Por favor selecciona una dirección de envío y método de envío')
    return
  }
  
  try {
    await calculateTotals({
      shipping_address_id: selectedAddress.id,
      shipping_method_id: selectedShippingMethod.id
    })
    setStep("payment")
  } catch (error) {
    console.error('Error al calcular totales:', error)
  }
}
```

### **4. Confirmación de Pedido**
```typescript
const handlePaymentSubmit = async () => {
  if (!selectedAddress || !selectedShippingMethod || !selectedPaymentMethod) {
    alert('Por favor completa toda la información requerida')
    return
  }
  
  try {
    await confirmOrder({
      shipping_address_id: selectedAddress.id,
      shipping_method_id: selectedShippingMethod.id,
      payment_method_id: selectedPaymentMethod.id,
      notes: customerInfo.notes
    })
  } catch (error) {
    console.error('Error al confirmar pedido:', error)
  }
}
```

## Características de UX

### **🎯 Estados de Carga**
- **Loading spinners** en todas las operaciones
- **Skeleton loading** para datos
- **Botones deshabilitados** durante operaciones
- **Indicadores visuales** de progreso

### **🚨 Manejo de Errores**
- **Mensajes de error** contextuales
- **Validaciones** en tiempo real
- **Reintentos automáticos** para operaciones fallidas
- **Fallbacks** para datos faltantes

### **📱 Responsive Design**
- **Mobile-first** approach
- **Touch-friendly** controles
- **Adaptive layouts** para diferentes pantallas
- **Optimización** para dispositivos móviles

### **🔐 Autenticación**
- **Verificación automática** de autenticación
- **Redirección** a login si no autenticado
- **Preservación** de URL de destino
- **Pre-llenado** de datos del usuario

## API Endpoints Utilizados

### **Backend (Laravel)**
```php
// CheckoutController.php
GET    /api/v1/checkout/initiate     // Iniciar checkout
POST   /api/v1/checkout/calculate    // Calcular totales
POST   /api/v1/checkout/confirm      // Confirmar pedido
POST   /api/v1/checkout/apply-coupon // Aplicar cupón
DELETE /api/v1/checkout/remove-coupon // Remover cupón
```

### **Frontend (Next.js)**
```typescript
// lib/api.ts
checkoutApi.initiate()
checkoutApi.calculate(data)
checkoutApi.confirm(data)
checkoutApi.applyCoupon(code)
checkoutApi.removeCoupon()
```

## Configuración

### **Variables de Entorno**
```env
# Frontend
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1

# Backend
SESSION_DRIVER=file
SESSION_DOMAIN=127.0.0.1
```

### **Autenticación**
```typescript
// El hook maneja automáticamente la autenticación
const { user, isAuthenticated } = useAuth()

// Redirección automática si no autenticado
if (!isAuthenticated) {
  router.push('/login?redirect=/checkout')
}
```

## Testing

### **Casos de Prueba**
1. ✅ **Usuario autenticado** - Flujo completo
2. ✅ **Usuario no autenticado** - Redirección a login
3. ✅ **Carrito vacío** - Mensaje apropiado
4. ✅ **Error de API** - Manejo de errores
5. ✅ **Validaciones** - Campos requeridos
6. ✅ **Estados de carga** - Spinners y loading
7. ✅ **Responsive** - Diferentes tamaños de pantalla

### **Comandos de Testing**
```bash
# Test de integración
npm run test:e2e checkout

# Test de componentes
npm run test components/checkout

# Test de hooks
npm run test hooks/useCheckout
```

## Performance

### **Optimizaciones**
- **Lazy loading** de componentes
- **Debounce** en validaciones
- **Memoización** de cálculos costosos
- **Cache** de datos de checkout
- **Optimización** de re-renders

### **Métricas Esperadas**
- **First Contentful Paint**: < 1.5s
- **Time to Interactive**: < 3s
- **Bundle Size**: < 100KB (checkout)
- **API Response Time**: < 500ms

## Troubleshooting

### **Problemas Comunes**

**1. Error de autenticación:**
```typescript
// Verificar token en localStorage
const token = localStorage.getItem('auth_token')
if (!token) {
  // Redirigir a login
}
```

**2. Error de CORS:**
```php
// Backend - config/cors.php
'allowed_origins' => ['http://localhost:3000'],
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
'allowed_headers' => ['*'],
```

**3. Error de validación:**
```typescript
// Frontend - validar antes de enviar
if (!selectedAddress || !selectedShippingMethod) {
  setError('Por favor completa todos los campos requeridos')
  return
}
```

### **Debug**
```typescript
// Habilitar logs de debug
const DEBUG = process.env.NODE_ENV === 'development'

if (DEBUG) {
  console.log('Checkout Debug:', {
    step,
    checkoutData,
    selectedAddress,
    selectedShippingMethod,
    selectedPaymentMethod
  })
}
```

## Próximas Mejoras

### **Funcionalidades Pendientes**
1. **Cupones** - Integración completa
2. **Múltiples direcciones** - Gestión avanzada
3. **Guardado de preferencias** - Recordar selecciones
4. **Analytics** - Tracking de conversión
5. **A/B testing** - Optimización de UX

### **Optimizaciones Futuras**
1. **Service Worker** - Cache offline
2. **Progressive Web App** - Instalación
3. **Push notifications** - Estado del pedido
4. **PWA features** - Background sync

---

**Estado**: ✅ Completado  
**Última actualización**: Diciembre 2024  
**Versión**: 1.0.0 