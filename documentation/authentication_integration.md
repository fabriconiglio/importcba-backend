# 🔐 Integración de Autenticación con Laravel Sanctum

## 📋 Resumen

Se ha implementado una integración completa de autenticación entre el frontend Next.js y el backend Laravel usando Laravel Sanctum para la gestión de tokens y sesiones.

## 🏗️ Arquitectura

### Backend (Laravel)
- **Laravel Sanctum**: Sistema de autenticación por tokens
- **Endpoints protegidos**: Rutas que requieren autenticación
- **Middleware de autenticación**: Verificación automática de tokens

### Frontend (Next.js)
- **Context de autenticación**: Estado global del usuario
- **Servicio de autenticación**: Manejo de API calls y tokens
- **Protección de rutas**: Hooks para verificar autenticación
- **Persistencia local**: Almacenamiento seguro de tokens

## 🔧 Implementación

### 1. Servicio de Autenticación (`auth-service.ts`)

```typescript
class AuthService {
  private token: string | null = null
  private user: User | null = null

  // Métodos principales
  async login(credentials: LoginData): Promise<AuthResult>
  async register(userData: RegisterData): Promise<AuthResult>
  async logout(): Promise<LogoutResult>
  async getCurrentUser(): Promise<UserResult>
  async validateToken(): Promise<boolean>
}
```

**Características:**
- ✅ Gestión automática de tokens
- ✅ Persistencia en localStorage
- ✅ Manejo de errores robusto
- ✅ Validación de tokens
- ✅ Headers de autorización automáticos

### 2. Contexto de Autenticación (`auth-context.tsx`)

```typescript
interface AuthContextType {
  user: User | null
  isLoading: boolean
  login: (credentials: LoginData) => Promise<AuthResult>
  register: (userData: RegisterData) => Promise<AuthResult>
  logout: () => Promise<void>
  forgotPassword: (email: string) => Promise<PasswordResult>
  refreshUser: () => Promise<void>
}
```

**Funcionalidades:**
- ✅ Estado global del usuario
- ✅ Loading states para UX
- ✅ Métodos de autenticación
- ✅ Refresh automático de datos

### 3. Protección de Rutas (`useProtectedRoute.ts`)

```typescript
// Hook para rutas que requieren autenticación
export function useRequireAuth(redirectTo?: string)

// Hook para rutas que no deben mostrar usuarios autenticados
export function useRequireGuest(redirectTo?: string)
```

**Características:**
- ✅ Redirección automática
- ✅ Verificación de estado de autenticación
- ✅ Loading states durante verificación
- ✅ Configuración flexible de redirecciones

## 🚀 Endpoints de la API

### Autenticación Pública
```http
POST /api/v1/auth/register
POST /api/v1/auth/login
POST /api/v1/auth/forgot-password
POST /api/v1/auth/reset-password
```

### Autenticación Protegida
```http
POST /api/v1/auth/logout
GET  /api/v1/auth/me
PUT  /api/v1/auth/profile
```

### Recursos Protegidos
```http
GET    /api/v1/cart
POST   /api/v1/cart/add
PUT    /api/v1/cart/update/{id}
DELETE /api/v1/cart/remove/{id}
DELETE /api/v1/cart/clear

GET    /api/v1/orders
GET    /api/v1/orders/{id}
GET    /api/v1/orders/status/{status}
GET    /api/v1/orders/stats
```

## 🔒 Flujo de Autenticación

### 1. **Registro de Usuario**
```
Usuario llena formulario → API /auth/register → Token generado → Usuario autenticado
```

### 2. **Login de Usuario**
```
Usuario ingresa credenciales → API /auth/login → Token validado → Sesión iniciada
```

### 3. **Verificación de Token**
```
Cada request → Headers con Bearer token → Middleware verifica → Acceso permitido/denegado
```

### 4. **Logout**
```
Usuario solicita logout → API /auth/logout → Token revocado → Sesión cerrada
```

## 📱 Páginas Implementadas

### ✅ **Login** (`/login`)
- Formulario de autenticación
- Validación de credenciales
- Manejo de errores
- Redirección automática

### ✅ **Registro** (`/register`)
- Formulario de registro
- Validación de datos
- Confirmación de contraseña
- Términos y condiciones

### ✅ **Mi Cuenta** (`/mi-cuenta`)
- Información del perfil
- Edición de datos
- Navegación lateral
- Acciones de cuenta

## 🛡️ Seguridad

### **Tokens**
- ✅ Generación automática en Laravel
- ✅ Almacenamiento seguro en localStorage
- ✅ Expiración y renovación
- ✅ Revocación en logout

### **Rutas Protegidas**
- ✅ Verificación automática de autenticación
- ✅ Redirección a login si no autenticado
- ✅ Middleware de autenticación en backend
- ✅ Headers de autorización automáticos

### **Validación**
- ✅ Validación de formularios en frontend
- ✅ Validación de datos en backend
- ✅ Manejo de errores de validación
- ✅ Sanitización de inputs

## 🔄 Estado de la Aplicación

### **Autenticado**
```typescript
{
  user: {
    id: "uuid",
    name: "Juan Pérez",
    email: "juan@example.com",
    phone: "+54 9 11 1234-5678"
  },
  token: "1|abc123...",
  isLoading: false
}
```

### **No Autenticado**
```typescript
{
  user: null,
  token: null,
  isLoading: false
}
```

### **Loading**
```typescript
{
  user: null,
  token: null,
  isLoading: true
}
```

## 🧪 Testing

### **Casos de Uso Verificados**
- ✅ Registro de nuevo usuario
- ✅ Login con credenciales válidas
- ✅ Login con credenciales inválidas
- ✅ Logout y limpieza de sesión
- ✅ Protección de rutas
- ✅ Persistencia de tokens
- ✅ Manejo de errores de API

### **Flujos de Usuario**
- ✅ Usuario no autenticado → Login/Registro
- ✅ Usuario autenticado → Mi Cuenta
- ✅ Usuario autenticado → Acceso a recursos protegidos
- ✅ Usuario autenticado → Logout → Redirección

## 🚀 Próximos Pasos

### **Funcionalidades Pendientes**
- [ ] Actualización de perfil en tiempo real
- [ ] Cambio de contraseña
- [ ] Recuperación de contraseña
- [ ] Verificación de email
- [ ] Autenticación social (Google, Facebook)

### **Mejoras Técnicas**
- [ ] Refresh tokens automático
- [ ] Interceptor de requests para renovación
- [ ] Cache de datos de usuario
- [ ] Offline support
- [ ] Analytics de autenticación

## 📚 Referencias

- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [Next.js Authentication](https://nextjs.org/docs/authentication)
- [React Context API](https://react.dev/reference/react/createContext)
- [TypeScript Interfaces](https://www.typescriptlang.org/docs/handbook/interfaces.html)

## 🔧 Configuración

### **Variables de Entorno**
```env
# Frontend (.env.local)
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1

# Backend (.env)
SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost
```

### **CORS (Backend)**
```php
// config/cors.php
'allowed_origins' => ['http://localhost:3000'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'credentials' => true,
```

### **Session (Backend)**
```php
// config/session.php
'domain' => env('SESSION_DOMAIN', 'localhost'),
'same_site' => 'lax',
```

---

**Estado**: ✅ **COMPLETADO**  
**Última actualización**: Diciembre 2024  
**Versión**: 1.0.0 