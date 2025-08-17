# Configuración de Emails

## Descripción
Guía completa para configurar el sistema de emails en el proyecto ecommerce.

## Variables de Entorno Requeridas

### Configuración Básica
```env
# Configuración de la aplicación
APP_NAME="Mi Ecommerce"
APP_URL=http://localhost:8000
APP_FRONTEND_URL=http://localhost:3000

# Configuración de email
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=tu-contraseña-de-aplicación
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Configuración para Gmail
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=tu-contraseña-de-aplicación
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu-email@gmail.com
MAIL_FROM_NAME="Mi Ecommerce"
```

### Configuración para Mailgun
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=tu-dominio.mailgun.org
MAILGUN_SECRET=tu-api-key
MAIL_FROM_ADDRESS=noreply@tu-dominio.com
MAIL_FROM_NAME="Mi Ecommerce"
```

### Configuración para SendGrid
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=tu-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tu-dominio.com
MAIL_FROM_NAME="Mi Ecommerce"
```

### Configuración para Amazon SES
```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=tu-access-key
AWS_SECRET_ACCESS_KEY=tu-secret-key
AWS_DEFAULT_REGION=us-east-1
MAIL_FROM_ADDRESS=noreply@tu-dominio.com
MAIL_FROM_NAME="Mi Ecommerce"
```

### Configuración para Mailtrap (Desarrollo Recomendado)
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu-username
MAIL_PASSWORD=tu-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tu-ecommerce.com
MAIL_FROM_NAME="Mi Ecommerce"
```

### Configuración para Desarrollo (Log)
```env
MAIL_MAILER=log
MAIL_LOG_CHANNEL=mail
```

## Configuración de Redes Sociales (Opcional)
```env
# Enlaces de redes sociales para los emails
APP_SOCIAL_FACEBOOK=https://facebook.com/tu-empresa
APP_SOCIAL_INSTAGRAM=https://instagram.com/tu-empresa
APP_SOCIAL_TWITTER=https://twitter.com/tu-empresa
```

## Configuración de Cola (Opcional)
```env
# Configuración de cola para emails
QUEUE_CONNECTION=database
QUEUE_DRIVER=database
```

## Pasos de Configuración

### 1. Configurar Mailtrap (Recomendado para desarrollo)

#### Paso 1: Crear cuenta en Mailtrap
1. Ve a [mailtrap.io](https://mailtrap.io)
2. Regístrate gratuitamente
3. Crea un nuevo inbox
4. Ve a "SMTP Settings" y copia las credenciales

#### Paso 2: Configurar Variables de Entorno
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu-username
MAIL_PASSWORD=tu-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tu-ecommerce.com
MAIL_FROM_NAME="Mi Ecommerce"
```

### 2. Configurar Gmail (Alternativa para desarrollo)

#### Paso 1: Habilitar Autenticación de 2 Factores
1. Ve a tu cuenta de Google
2. Activa la verificación en 2 pasos
3. Ve a "Contraseñas de aplicación"
4. Genera una nueva contraseña para "Correo"

#### Paso 2: Configurar Variables de Entorno
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=la-contraseña-de-aplicación-generada
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu-email@gmail.com
MAIL_FROM_NAME="Mi Ecommerce"
```

### 3. Configurar Mailgun (Recomendado para producción)

#### Paso 1: Crear cuenta en Mailgun
1. Regístrate en [mailgun.com](https://mailgun.com)
2. Verifica tu dominio
3. Obtén las credenciales de API

#### Paso 2: Configurar Variables de Entorno
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=tu-dominio.mailgun.org
MAILGUN_SECRET=tu-api-key
MAIL_FROM_ADDRESS=noreply@tu-dominio.com
MAIL_FROM_NAME="Mi Ecommerce"
```

### 4. Configurar Cola de Emails

#### Paso 1: Crear tabla de colas
```bash
php artisan queue:table
php artisan migrate
```

#### Paso 2: Configurar variables de entorno
```env
QUEUE_CONNECTION=database
QUEUE_DRIVER=database
```

#### Paso 3: Procesar colas
```bash
# En desarrollo
php artisan queue:work

# En producción (con supervisor)
php artisan queue:work --daemon
```

## Verificación de Configuración

### 1. Verificar Configuración
```bash
# Verificar configuración de email
php artisan tinker
>>> app('App\Services\EmailService')->checkEmailConfiguration()
```

### 2. Enviar Email de Prueba
```bash
# Usando el endpoint de la API
curl -X POST http://localhost:8000/api/v1/emails/test \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "tu-email@ejemplo.com",
    "type": "welcome"
  }'
```

### 3. Verificar Logs
```bash
# Ver logs de email
tail -f storage/logs/laravel.log | grep -i mail
```

## Tipos de Emails Implementados

### 1. Confirmación de Pedido
- **Trigger**: Al confirmar un pedido
- **Contenido**: Detalles del pedido, productos, totales, direcciones
- **Plantilla**: `resources/views/emails/orders/confirmation.blade.php`

### 2. Recuperación de Contraseña
- **Trigger**: Al solicitar recuperación de contraseña
- **Contenido**: Enlace de recuperación, instrucciones
- **Plantilla**: `resources/views/emails/auth/password-reset.blade.php`

### 3. Email de Bienvenida
- **Trigger**: Al registrar un nuevo usuario
- **Contenido**: Mensaje de bienvenida, características del sitio
- **Plantilla**: `resources/views/emails/auth/welcome.blade.php`

## Endpoints de la API

### Emails de Pedidos
- `POST /api/v1/emails/order/{orderId}/confirmation` - Enviar confirmación inmediata
- `POST /api/v1/emails/order/{orderId}/confirmation/queue` - Encolar confirmación

### Emails de Autenticación
- `POST /api/v1/emails/password-reset` - Enviar recuperación de contraseña
- `POST /api/v1/emails/password-reset/queue` - Encolar recuperación de contraseña
- `POST /api/v1/emails/welcome/{userId}` - Enviar email de bienvenida
- `POST /api/v1/emails/welcome/{userId}/queue` - Encolar email de bienvenida

### Administración
- `GET /api/v1/emails/check-configuration` - Verificar configuración
- `GET /api/v1/emails/stats` - Obtener estadísticas
- `POST /api/v1/emails/test` - Enviar email de prueba

## Ejemplos de Uso

### Frontend - Solicitar Recuperación de Contraseña
```javascript
const requestPasswordReset = async (email) => {
  const response = await fetch('/api/v1/emails/password-reset', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ email })
  });
  
  const result = await response.json();
  
  if (result.success) {
    showMessage('Se ha enviado un email con las instrucciones');
  } else {
    showError(result.message);
  }
};
```

### Backend - Enviar Email de Bienvenida
```php
// En el controlador de registro
public function register(Request $request)
{
    // ... lógica de registro
    
    $user = User::create($userData);
    
    // Enviar email de bienvenida
    $emailService = app(EmailService::class);
    $emailService->queueWelcome($user);
    
    return response()->json([
        'success' => true,
        'message' => 'Usuario registrado correctamente'
    ]);
}
```

## Solución de Problemas

### Error: "SMTP connect() failed"
- Verificar credenciales de SMTP
- Verificar puerto y encriptación
- Verificar firewall/antivirus

### Error: "Authentication failed"
- Verificar usuario y contraseña
- Para Gmail: usar contraseña de aplicación
- Verificar configuración de 2FA

### Error: "Connection refused"
- Verificar host y puerto
- Verificar conectividad de red
- Verificar configuración del servidor

### Emails no se envían
- Verificar configuración de cola
- Verificar logs de Laravel
- Verificar configuración de mail driver

## Configuración de Producción

### 1. Usar Servicio de Email Confiable
- Mailgun, SendGrid, Amazon SES
- Evitar Gmail para producción

### 2. Configurar Colas
- Usar Redis o Database para colas
- Configurar supervisor para procesar colas

### 3. Configurar Logs
- Configurar logs de email
- Monitorear tasa de entrega

### 4. Configurar DNS
- Configurar registros SPF, DKIM, DMARC
- Verificar dominio en servicio de email

## Comandos Útiles

```bash
# Verificar configuración
php artisan tinker
>>> config('mail')

# Procesar colas
php artisan queue:work

# Limpiar colas fallidas
php artisan queue:flush

# Ver estadísticas de cola
php artisan queue:failed
```

## Notas Importantes

1. **Seguridad**: Nunca committear credenciales reales
2. **Desarrollo**: Usar `log` driver para desarrollo local
3. **Producción**: Usar servicios confiables como Mailgun
4. **Colas**: Siempre usar colas en producción
5. **Monitoreo**: Configurar alertas para emails fallidos 