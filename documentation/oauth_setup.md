# 🔐 Configuración de OAuth para Google y Facebook

## 📋 Resumen

Esta guía explica cómo configurar la autenticación social con Google y Facebook en el proyecto Laravel + Next.js.

## 🚀 Configuración del Backend (Laravel)

### 1. **Variables de Entorno**

Agrega estas variables a tu archivo `.env`:

```env
# Google OAuth
GOOGLE_CLIENT_ID=tu_google_client_id
GOOGLE_CLIENT_SECRET=tu_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/v1/auth/google/callback

# Facebook OAuth
FACEBOOK_CLIENT_ID=tu_facebook_client_id
FACEBOOK_CLIENT_SECRET=tu_facebook_client_secret
FACEBOOK_REDIRECT_URI=http://localhost:8000/api/v1/auth/facebook/callback

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost
```

### 2. **Configuración de Servicios**

El archivo `config/services.php` ya está configurado con:

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],

'facebook' => [
    'client_id' => env('FACEBOOK_CLIENT_ID'),
    'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    'redirect' => env('FACEBOOK_REDIRECT_URI'),
],
```

### 3. **Rutas de OAuth**

Las rutas ya están configuradas en `routes/api.php`:

```php
// Rutas de autenticación social
Route::get('{provider}/redirect', [SocialAuthController::class, 'redirectToProvider']);
Route::get('{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('{provider}/disconnect', [SocialAuthController::class, 'disconnectProvider']);
});
```

## 🔧 Configuración de Google OAuth

### 1. **Crear Proyecto en Google Cloud Console**

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto o selecciona uno existente
3. Habilita la API de Google+ API

### 2. **Configurar Credenciales OAuth 2.0**

1. Ve a "APIs & Services" > "Credentials"
2. Haz clic en "Create Credentials" > "OAuth 2.0 Client IDs"
3. Selecciona "Web application"
4. Configura las URIs autorizadas:
   - **Authorized JavaScript origins**: `http://localhost:3000`
   - **Authorized redirect URIs**: `http://localhost:8000/api/v1/auth/google/callback`

### 3. **Obtener Credenciales**

Copia el `Client ID` y `Client Secret` a tu archivo `.env`.

## 🔧 Configuración de Facebook OAuth

### 1. **Crear Aplicación en Facebook Developers**

1. Ve a [Facebook Developers](https://developers.facebook.com/)
2. Crea una nueva aplicación
3. Selecciona "Consumer" como tipo de aplicación

### 2. **Configurar OAuth**

1. Ve a "Products" > "Facebook Login"
2. Configura las URLs:
   - **Valid OAuth Redirect URIs**: `http://localhost:8000/api/v1/auth/facebook/callback`
   - **Site URL**: `http://localhost:3000`

### 3. **Obtener Credenciales**

Copia el `App ID` y `App Secret` a tu archivo `.env`.

## 🎯 Endpoints de la API

### **Redirigir a Proveedor OAuth**
```http
GET /api/v1/auth/{provider}/redirect
```

**Parámetros:**
- `provider`: `google` o `facebook`

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "redirect_url": "https://accounts.google.com/oauth/authorize?..."
  }
}
```

### **Callback de OAuth**
```http
GET /api/v1/auth/{provider}/callback
```

**Parámetros:**
- `provider`: `google` o `facebook`
- `code`: Código de autorización (enviado por el proveedor)

**Respuesta:**
```json
{
  "success": true,
  "message": "¡Autenticación exitosa!",
  "data": {
    "user": {
      "id": "uuid",
      "name": "Juan Pérez",
      "email": "juan@gmail.com",
      "provider": "google",
      "provider_id": "123456789"
    },
    "token": "1|abc123..."
  }
}
```

### **Desconectar Proveedor**
```http
POST /api/v1/auth/{provider}/disconnect
```

**Headers:**
```
Authorization: Bearer {token}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Proveedor desconectado exitosamente"
}
```

## 🔄 Flujo de Autenticación

### **1. Inicio de OAuth**
```
Usuario hace clic en "Continuar con Google/Facebook"
↓
Frontend llama a /api/v1/auth/{provider}/redirect
↓
Backend retorna URL de redirección
↓
Frontend redirige al usuario al proveedor OAuth
```

### **2. Autorización del Usuario**
```
Usuario autoriza la aplicación en Google/Facebook
↓
Proveedor redirige a /api/v1/auth/{provider}/callback
↓
Backend procesa el callback y crea/actualiza usuario
↓
Backend retorna token de autenticación
```

### **3. Autenticación Completada**
```
Frontend recibe token y datos del usuario
↓
Usuario queda autenticado en la aplicación
↓
Token se almacena en localStorage
↓
Usuario puede acceder a recursos protegidos
```

## 🛡️ Seguridad

### **Validaciones Implementadas**
- ✅ Verificación de proveedores soportados
- ✅ Validación de tokens OAuth
- ✅ Sanitización de datos del usuario
- ✅ Manejo seguro de contraseñas
- ✅ Verificación de email por OAuth

### **Buenas Prácticas**
- 🔒 Usar HTTPS en producción
- 🔒 Validar URIs de redirección
- 🔒 Implementar rate limiting
- 🔒 Logging de eventos de autenticación
- 🔒 Manejo de errores robusto

## 🧪 Testing

### **Probar con Postman**

1. **Redirección:**
   ```
   GET http://localhost:8000/api/v1/auth/google/redirect
   ```

2. **Callback (simulado):**
   ```
   GET http://localhost:8000/api/v1/auth/google/callback?code=test_code
   ```

### **Probar en Frontend**

1. Configura las credenciales en `.env`
2. Inicia el backend Laravel
3. Inicia el frontend Next.js
4. Haz clic en los botones de OAuth
5. Verifica el flujo completo

## 🚀 Próximos Pasos

### **Funcionalidades Pendientes**
- [ ] Refresh tokens automático
- [ ] Vinculación de múltiples proveedores
- [ ] Sincronización de datos de perfil
- [ ] Notificaciones de seguridad
- [ ] Analytics de autenticación social

### **Mejoras Técnicas**
- [ ] Cache de datos de usuario OAuth
- [ ] Webhooks para cambios de perfil
- [ ] Migración de cuentas existentes
- [ ] Backup de datos sociales
- [ ] Auditoría de conexiones OAuth

## 📚 Referencias

- [Laravel Socialite Documentation](https://laravel.com/docs/socialite)
- [Google OAuth 2.0](https://developers.google.com/identity/protocols/oauth2)
- [Facebook Login](https://developers.facebook.com/docs/facebook-login/)
- [OAuth 2.0 Security Best Practices](https://tools.ietf.org/html/draft-ietf-oauth-security-topics)

---

**Estado**: ✅ **IMPLEMENTADO**  
**Última actualización**: Diciembre 2024  
**Versión**: 1.0.0 