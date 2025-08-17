# 🚀 Implementación de OAuth en el Frontend (Next.js)

## 📋 Resumen

Esta documentación explica cómo se implementó la autenticación social con Google y Facebook en el frontend Next.js, incluyendo la integración con el backend Laravel Sanctum.

## 🏗️ Arquitectura del Frontend

### **Componentes Implementados:**

#### **1. Servicio de Autenticación (`auth-service.ts`)**
```typescript
// Métodos de OAuth agregados
async socialAuth(provider: 'google' | 'facebook'): Promise<AuthResult>
async handleOAuthCallback(provider: 'google' | 'facebook', code: string): Promise<AuthResult>
```

#### **2. Contexto de Autenticación (`auth-context.tsx`)**
```typescript
interface AuthContextType {
  // ... métodos existentes
  socialAuth: (provider: 'google' | 'facebook') => Promise<AuthResult>
}
```

#### **3. Hooks de Protección (`useProtectedRoute.ts`)**
```typescript
// Hooks para proteger rutas según estado de autenticación
useRequireAuth(redirectTo?: string)
useRequireGuest(redirectTo?: string)
```

## 🔧 Implementación de OAuth

### **Flujo de Autenticación Social:**

#### **1. Inicio de OAuth**
```typescript
const handleSocialAuth = async (provider: 'google' | 'facebook') => {
  setIsLoading(true)
  setError("")
  
  try {
    const result = await socialAuth(provider)
    if (result.success && result.redirectUrl) {
      // Redirigir al usuario al proveedor OAuth
      window.location.href = result.redirectUrl
    } else {
      setError(result.error || `Error al conectar con ${provider}`)
    }
  } catch (error) {
    setError(`Error al conectar con ${provider}`)
  } finally {
    setIsLoading(false)
  }
}
```

#### **2. Botones de OAuth**
```tsx
<Button
  variant="outline"
  className="w-full"
  onClick={() => handleSocialAuth('google')}
  disabled={isLoading}
>
  <svg className="w-5 h-5" viewBox="0 0 24 24">
    {/* Icono de Google */}
  </svg>
  {isLoading ? 'Conectando...' : 'Google'}
</Button>
```

### **Estados de Loading:**

- ✅ **Botones deshabilitados** durante la conexión
- ✅ **Texto dinámico** ("Conectando..." vs "Google"/"Facebook")
- ✅ **Indicadores visuales** de estado de carga
- ✅ **Manejo de errores** con mensajes descriptivos

## 📱 Páginas Implementadas

### **✅ Login (`/login`)**
- Formulario de autenticación tradicional
- Botones de OAuth funcionales
- Manejo de estados de loading
- Redirección automática después del login

### **✅ Registro (`/register`)**
- Formulario de registro completo
- Botones de OAuth funcionales
- Validación de términos y condiciones
- Confirmación de contraseña

### **✅ Mi Cuenta (`/mi-cuenta`)**
- Dashboard protegido del usuario
- Información del perfil
- Opciones de edición
- Navegación lateral con funcionalidades

## 🔄 Flujo de Usuario

### **Usuario Nuevo:**
```
1. Usuario visita /register
2. Hace clic en "Continuar con Google/Facebook"
3. Es redirigido al proveedor OAuth
4. Autoriza la aplicación
5. Es redirigido de vuelta con código
6. Backend crea cuenta y retorna token
7. Usuario queda autenticado y va a /mi-cuenta
```

### **Usuario Existente:**
```
1. Usuario visita /login
2. Hace clic en "Continuar con Google/Facebook"
3. Es redirigido al proveedor OAuth
4. Autoriza la aplicación
5. Backend encuentra cuenta existente
6. Retorna token de autenticación
7. Usuario queda autenticado y va a /mi-cuenta
```

## 🛡️ Seguridad Implementada

### **Protección de Rutas:**
- ✅ **Rutas públicas**: Login, registro, home
- ✅ **Rutas protegidas**: Mi cuenta, carrito, pedidos
- ✅ **Redirección automática** según estado de autenticación
- ✅ **Verificación de tokens** en cada request

### **Manejo de Estados:**
- ✅ **Loading states** durante operaciones
- ✅ **Error handling** robusto
- ✅ **Validación de formularios** en frontend
- ✅ **Persistencia segura** de tokens

### **UX/UI:**
- ✅ **Botones deshabilitados** durante operaciones
- ✅ **Mensajes de error** claros y descriptivos
- ✅ **Indicadores visuales** de estado
- ✅ **Redirección automática** después de operaciones exitosas

## 🔧 Configuración

### **Variables de Entorno:**
```env
# API Configuration
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1

# OAuth Configuration (opcional para desarrollo)
NEXT_PUBLIC_GOOGLE_CLIENT_ID=your_google_client_id
NEXT_PUBLIC_FACEBOOK_CLIENT_ID=your_facebook_client_id

# App Configuration
NEXT_PUBLIC_APP_NAME="Import Mayorista"
NEXT_PUBLIC_APP_URL=http://localhost:3000
```

### **Dependencias:**
```json
{
  "dependencies": {
    "next": "^14.0.0",
    "react": "^18.0.0",
    "react-dom": "^18.0.0"
  }
}
```

## 🧪 Testing

### **Casos de Prueba:**

#### **1. Flujo de Login OAuth:**
- ✅ Usuario hace clic en botón Google/Facebook
- ✅ Redirección al proveedor OAuth
- ✅ Autorización exitosa
- ✅ Retorno con token válido
- ✅ Usuario autenticado en la app

#### **2. Flujo de Registro OAuth:**
- ✅ Usuario nuevo hace clic en OAuth
- ✅ Creación automática de cuenta
- ✅ Asignación de rol "customer"
- ✅ Verificación de email automática
- ✅ Login inmediato después del registro

#### **3. Manejo de Errores:**
- ✅ Error de conexión con proveedor
- ✅ Usuario cancela autorización
- ✅ Token expirado o inválido
- ✅ Error en creación de cuenta

### **Herramientas de Testing:**
- **Postman**: Para probar endpoints de OAuth
- **Browser DevTools**: Para monitorear requests
- **React DevTools**: Para inspeccionar estado
- **Network Tab**: Para verificar flujo de OAuth

## 🚀 Funcionalidades Avanzadas

### **Implementadas:**
- ✅ **Autenticación social** con Google y Facebook
- ✅ **Protección de rutas** automática
- ✅ **Manejo de estados** de loading
- ✅ **Persistencia de sesión** en localStorage
- ✅ **Manejo de errores** robusto

### **Pendientes (Futuras):**
- [ ] **Refresh tokens** automático
- [ ] **Vinculación múltiple** de proveedores
- [ ] **Sincronización de perfiles** OAuth
- [ ] **Notificaciones push** de seguridad
- [ ] **Analytics de autenticación** social

## 🔍 Debugging

### **Logs del Frontend:**
```typescript
console.log('Social auth initiated:', provider)
console.log('OAuth redirect URL:', result.redirectUrl)
console.log('OAuth callback result:', result)
```

### **Logs del Backend:**
```php
Log::info("OAuth redirect initiated for provider: {$provider}")
Log::error("OAuth callback error: " . $e->getMessage())
```

### **Verificación de Estado:**
```typescript
// En el contexto de autenticación
console.log('Current user:', user)
console.log('Auth token:', authService.getToken())
console.log('Is authenticated:', authService.isAuthenticated())
```

## 📚 Referencias

### **Documentación:**
- [Next.js Authentication](https://nextjs.org/docs/authentication)
- [React Context API](https://react.dev/reference/react/createContext)
- [TypeScript Interfaces](https://www.typescriptlang.org/docs/handbook/interfaces.html)

### **Librerías:**
- [Laravel Socialite](https://laravel.com/docs/socialite)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)

### **OAuth Providers:**
- [Google OAuth 2.0](https://developers.google.com/identity/protocols/oauth2)
- [Facebook Login](https://developers.facebook.com/docs/facebook-login/)

---

**Estado**: ✅ **IMPLEMENTADO**  
**Última actualización**: Diciembre 2024  
**Versión**: 1.0.0 