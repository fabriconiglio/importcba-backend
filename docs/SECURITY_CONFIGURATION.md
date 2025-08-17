# Configuración de Seguridad - CORS y Tokens

## 📋 Resumen

Este documento describe la configuración de seguridad implementada para el manejo de CORS (Cross-Origin Resource Sharing) y la gestión segura de tokens de autenticación.

## 🔒 Configuración de CORS

### Variables de Entorno

```env
# Configuración CORS
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000
CORS_MAX_AGE=0
CORS_SUPPORTS_CREDENTIALS=true
```

### Configuración por Entorno

#### Desarrollo Local
```env
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000
CORS_SUPPORTS_CREDENTIALS=true
```

#### Producción
```env
CORS_ALLOWED_ORIGINS=https://tu-dominio.com,https://www.tu-dominio.com
CORS_MAX_AGE=86400
CORS_SUPPORTS_CREDENTIALS=true
```

### Archivo de Configuración

**`config/cors.php`**
```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://127.0.0.1:3000') ? explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://127.0.0.1:3000')) : ['http://localhost:3000', 'http://127.0.0.1:3000'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => env('CORS_MAX_AGE', 0),
    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', true),
];
```

## 🔐 Configuración de Sanctum (Tokens)

### Variables de Entorno

```env
# Configuración Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000
SANCTUM_TOKEN_EXPIRATION=null
SANCTUM_TOKEN_PREFIX=
```

### Configuración por Entorno

#### Desarrollo Local
```env
SANCTUM_STATEFUL_DOMAINS=localhost:3000
SANCTUM_TOKEN_EXPIRATION=null
```

#### Producción
```env
SANCTUM_STATEFUL_DOMAINS=tu-dominio.com,www.tu-dominio.com
SANCTUM_TOKEN_EXPIRATION=1440
SANCTUM_TOKEN_PREFIX=sk_
```

### Archivo de Configuración

**`config/sanctum.php`**
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
))),

'expiration' => env('SANCTUM_TOKEN_EXPIRATION', null),
'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
```

## 🛡️ Rate Limiting para Tokens

### Variables de Entorno

```env
# Rate Limiting
TOKEN_RATE_LIMIT_MAX_ATTEMPTS=60
TOKEN_RATE_LIMIT_DECAY_MINUTES=1
```

### Configuración por Entorno

#### Desarrollo Local
```env
TOKEN_RATE_LIMIT_MAX_ATTEMPTS=100
TOKEN_RATE_LIMIT_DECAY_MINUTES=1
```

#### Producción
```env
TOKEN_RATE_LIMIT_MAX_ATTEMPTS=30
TOKEN_RATE_LIMIT_DECAY_MINUTES=5
```

### Middleware Implementado

**`app/Http/Middleware/TokenRateLimit.php`**

Características:
- ✅ Rate limiting por usuario autenticado o IP
- ✅ Headers de rate limit en respuestas
- ✅ Respuestas JSON con información de retry
- ✅ Configuración flexible por entorno

### Rutas Protegidas

Las siguientes rutas tienen rate limiting aplicado:

```php
Route::post('login', [AuthController::class, 'login'])->middleware('token.rate.limit');
Route::post('register', [AuthController::class, 'register'])->middleware('token.rate.limit');
Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('token.rate.limit');
Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('token.rate.limit');
```

## 🌐 Configuración de Frontend URL

### Variable de Entorno

```env
FRONTEND_URL=http://localhost:3000
```

### Configuración por Entorno

#### Desarrollo Local
```env
FRONTEND_URL=http://localhost:3000
```

#### Producción
```env
FRONTEND_URL=https://tu-dominio.com
```

### Uso en la Aplicación

La URL del frontend se utiliza en:
- Enlaces en emails de confirmación
- URLs de reset de contraseña
- Enlaces de verificación de email
- URLs de órdenes

## 🔧 Middleware Configurado

### `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware) {
    // Configurar Sanctum para API
    $middleware->api(prepend: [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    ]);
    
    // Configurar CORS para API
    $middleware->api(prepend: [
        \Illuminate\Http\Middleware\HandleCors::class,
    ]);

    $middleware->alias([
        'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        'token.rate.limit' => \App\Http\Middleware\TokenRateLimit::class,
    ]);
})
```

## 🚀 Despliegue por Entorno

### Desarrollo Local

1. **CORS**: Permitir localhost:3000
2. **Sanctum**: Sin expiración de tokens
3. **Rate Limiting**: Límites más permisivos
4. **Frontend URL**: localhost:3000

### Staging

1. **CORS**: Permitir dominio de staging
2. **Sanctum**: Tokens con expiración moderada
3. **Rate Limiting**: Límites estándar
4. **Frontend URL**: URL de staging

### Producción

1. **CORS**: Solo dominios de producción
2. **Sanctum**: Tokens con expiración estricta
3. **Rate Limiting**: Límites estrictos
4. **Frontend URL**: URL de producción

## 🔍 Monitoreo y Logs

### Headers de Rate Limiting

Las respuestas incluyen headers informativos:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1640995200
```

### Respuestas de Error

Cuando se excede el rate limit:

```json
{
    "message": "Too many authentication attempts. Please try again later.",
    "retry_after": 60
}
```

## 🛠️ Comandos Útiles

### Limpiar Cache de Configuración

```bash
php artisan config:clear
php artisan config:cache
```

### Verificar Configuración

```bash
php artisan config:show cors
php artisan config:show sanctum
```

### Limpiar Tokens Expirados

```bash
php artisan sanctum:prune-expired
```

## 🔐 Mejores Prácticas

### Seguridad

1. **Nunca exponer tokens en logs**
2. **Usar HTTPS en producción**
3. **Configurar expiración de tokens**
4. **Implementar rate limiting**
5. **Validar orígenes CORS**

### Performance

1. **Cachear configuración en producción**
2. **Usar rate limiting apropiado**
3. **Configurar max_age para CORS**
4. **Limpiar tokens expirados regularmente**

### Mantenimiento

1. **Revisar logs de rate limiting**
2. **Monitorear intentos de autenticación**
3. **Actualizar orígenes CORS según necesidad**
4. **Rotar tokens en caso de compromiso**

## 📞 Soporte

Para problemas de configuración de seguridad:

1. Verificar variables de entorno
2. Revisar logs de Laravel
3. Validar configuración de CORS
4. Comprobar rate limiting
5. Verificar expiración de tokens 