# Solución de Problemas de Deployment

## Problemas Resueltos

### 1. Error de Base de Datos
**Error Original:**
```
./deploy-script.sh: line 13: mysqldump: command not found
```

**Causa:** El script estaba hardcodeado para MySQL pero el servidor usa PostgreSQL.

**Solución:** Detección automática de tipo de base de datos (PostgreSQL/MySQL).

### 2. Error de Autenticación Git
**Error Original:**
```
fatal: could not read Username for 'https://github.com': No such device or address
```

**Causa:** El repositorio estaba configurado para usar HTTPS sin credenciales configuradas.

**Solución:** Conversión automática de HTTPS a SSH y configuración de Git.

## Scripts Disponibles

### 1. Script de Deployment Principal
**Archivo:** `deploy-script.sh`

**Uso:**
```bash
./deploy-script.sh
```

**Características:**
- ✅ Detección automática de tipo de base de datos (PostgreSQL/MySQL)
- ✅ Backup automático antes del deployment
- ✅ Restauración de .env después de git pull
- ✅ Instalación de dependencias y build de assets
- ✅ Limpieza de caché y optimización
- ✅ Health check y rollback automático

### 2. Script de Verificación de Base de Datos
**Archivo:** `scripts/check-database.sh`

**Uso:**
```bash
./scripts/check-database.sh
```

**Características:**
- 🔍 Verifica configuración de .env
- 🔧 Detecta clientes de BD disponibles
- 🔌 Prueba conexión a la base de datos
- 🎯 Verifica conexión desde Laravel
- 💡 Proporciona recomendaciones

### 3. Script de Verificación de Git
**Archivo:** `scripts/check-git-config.sh`

**Uso:**
```bash
./scripts/check-git-config.sh
```

**Características:**
- 🔍 Verifica configuración de Git
- 🔑 Prueba acceso SSH a GitHub
- 🌐 Prueba acceso HTTPS a GitHub
- 🔧 Configura Git automáticamente
- 🧪 Prueba fetch del repositorio

### 4. Script de Verificación de Dependencias
**Archivo:** `scripts/check-dependencies.sh`

**Uso:**
```bash
./scripts/check-dependencies.sh
```

**Características:**
- 🔍 Verifica todas las dependencias del sistema
- 📦 Verifica PHP, Composer, Node.js, npm
- 🗄️ Verifica clientes de base de datos
- 🔧 Verifica herramientas del sistema (Git, curl)
- 📁 Verifica permisos de directorios críticos
- 🧪 Verifica archivos de configuración

## Cómo Usar

### Antes del Deployment
1. **Verificar configuración de BD:**
   ```bash
   ./scripts/check-database.sh
   ```

2. **Verificar configuración de Git:**
   ```bash
   ./scripts/check-git-config.sh
   ```

3. **Verificar dependencias del sistema:**
   ```bash
   ./scripts/check-dependencies.sh
   ```

4. **Probar script localmente:**
   ```bash
   ./deploy-script.sh
   ```

### Deployment Automático (GitHub Actions)
- Se ejecuta automáticamente al hacer push a `main`
- Usa el script actualizado con detección automática de BD
- Configura Git automáticamente para usar SSH
- Incluye backup automático y rollback

## Configuración Requerida

### Variables de Entorno (.env)
```env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=nombre_base_datos
DB_USERNAME=usuario
DB_PASSWORD=contraseña
```

### Clientes de Base de Datos
- **PostgreSQL:** `psql` y `pg_dump` deben estar disponibles
- **MySQL:** `mysql` y `mysqldump` deben estar disponibles

### Configuración Git
- **SSH Keys:** Clave privada en el servidor, pública en GitHub
- **Remote Origin:** Debe estar configurado como `git@github.com:usuario/repositorio.git`
- **Permisos:** Usuario debe tener acceso al repositorio

### Permisos
- El usuario debe poder ejecutar `sudo -u postgres` (PostgreSQL)
- El usuario debe tener acceso a la base de datos
- El usuario debe tener permisos de escritura en el directorio del proyecto

## Solución de Problemas Comunes

### 1. Cliente de BD No Encontrado
**Error:** `psql: command not found` o `mysqldump: command not found`

**Solución:**
```bash
# Para PostgreSQL
sudo apt-get install postgresql-client

# Para MySQL
sudo apt-get install mysql-client
```

### 2. Error de Permisos
**Error:** `permission denied` o `access denied`

**Solución:**
```bash
# Verificar permisos
ls -la /var/www/importcba-backend

# Corregir permisos
sudo chown -R www-data:www-data /var/www/importcba-backend
sudo chmod -R 755 /var/www/importcba-backend
sudo chmod -R 775 storage bootstrap/cache
```

### 3. Error de Conexión a BD
**Error:** `could not connect to server` o `authentication failed`

**Solución:**
1. Verificar credenciales en .env
2. Verificar que el servicio de BD esté corriendo
3. Verificar firewall y configuración de red

### 4. Error de Migración
**Error:** `migration failed` o `table already exists`

**Solución:**
```bash
# Verificar estado de migraciones
php artisan migrate:status

# Forzar migraciones
php artisan migrate --force

# Si hay conflictos, revisar logs
tail -f storage/logs/laravel.log
```

### 5. Error de Autenticación Git
**Error:** `fatal: could not read Username for 'https://github.com'`

**Solución:**
```bash
# Verificar configuración de Git
./scripts/check-git-config.sh

# Configurar remote SSH manualmente
git remote set-url origin git@github.com:usuario/repositorio.git

# Verificar acceso SSH
ssh -T git@github.com
```

### 6. Error de Fetch Git
**Error:** `fatal: remote error: access denied`

**Solución:**
1. Verificar que la clave SSH esté agregada a GitHub
2. Verificar permisos del repositorio
3. Verificar que la rama `main` exista
4. Ejecutar: `./scripts/check-git-config.sh`

## Monitoreo del Deployment

### Logs Importantes
```bash
# Log de Laravel
tail -f storage/logs/laravel.log

# Log de Nginx
sudo tail -f /var/log/nginx/error.log

# Log de PHP-FPM
sudo tail -f /var/log/php8.3-fpm.log
```

### Health Checks
```bash
# Verificar API
curl -f https://importcbamayorista.com/api/v1/health

# Verificar Admin Panel
curl -f https://importcbamayorista.com/admin

# Verificar logs en tiempo real
tail -f storage/logs/laravel.log
```

## Comandos de Mantenimiento

### Limpieza de Caché
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Optimización
```bash
php artisan config:cache
php artisan route:cache
php artisan optimize
```

### Verificación de Estado
```bash
php artisan migrate:status
php artisan route:list
php artisan config:show
```

### Verificación de Git
```bash
# Verificar configuración
./scripts/check-git-config.sh

# Verificar remotes
git remote -v

# Verificar estado
git status
```

## Flujo de Deployment Recomendado

### 1. Preparación
```bash
# Verificar configuración
./scripts/check-database.sh
./scripts/check-git-config.sh
./scripts/check-dependencies.sh

# Verificar cambios pendientes
git status
git log --oneline -5
```

### 2. Deployment
```bash
# Opción A: Deployment automático (GitHub Actions)
git push origin main

# Opción B: Deployment manual
./deploy-script.sh
```

### 3. Verificación
```bash
# Health check
curl -f https://importcbamayorista.com/api/v1/health

# Verificar logs
tail -f storage/logs/laravel.log

# Verificar servicios
systemctl status php8.3-fpm
systemctl status nginx
```

## Próximos Pasos

1. **Probar** los scripts de verificación
2. **Verificar** que el deployment automático funcione
3. **Monitorear** los logs durante el próximo deployment
4. **Documentar** cualquier problema adicional encontrado

## Contacto y Soporte

Si encuentras problemas adicionales:
1. Revisa los logs del servidor
2. Ejecuta los scripts de verificación
3. Verifica la configuración del .env
4. Consulta la documentación de Laravel y Filament 