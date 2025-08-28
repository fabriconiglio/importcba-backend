#!/bin/bash

# Script de despliegue para el backend
# Uso: ./deploy-script.sh

set -e  # Salir si hay algún error

echo "Starting deployment..."

# Variables - ACTUALIZADO
PROJECT_DIR="/var/www/importcba-backend"
BACKUP_DIR="/var/www/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Crear directorio de backups si no existe
mkdir -p $BACKUP_DIR

# Navegar al directorio del proyecto
cd $PROJECT_DIR

# Detectar tipo de base de datos y hacer backup
echo "Detecting database type and creating backup..."
if command -v psql &> /dev/null; then
  echo "PostgreSQL detected, creating backup..."
  # Obtener nombre de la BD desde .env
  if [ -f .env ]; then
    DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2)
    if [ -n "$DB_NAME" ]; then
      sudo -u postgres pg_dump $DB_NAME > $BACKUP_DIR/backup_before_deploy_$TIMESTAMP.sql
      echo "PostgreSQL backup created: backup_before_deploy_$TIMESTAMP.sql"
    else
      echo "Warning: Could not determine database name from .env"
    fi
  else
    echo "Warning: .env file not found, skipping database backup"
  fi
elif command -v mysqldump &> /dev/null; then
  echo "MySQL detected, creating backup..."
  if [ -f .env ]; then
    DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2)
    if [ -n "$DB_NAME" ]; then
      mysqldump -u root -p$MYSQL_ROOT_PASSWORD $DB_NAME > $BACKUP_DIR/backup_before_deploy_$TIMESTAMP.sql
      echo "MySQL backup created: backup_before_deploy_$TIMESTAMP.sql"
    else
      echo "Warning: Could not determine database name from .env"
    fi
  else
    echo "Warning: .env file not found, skipping database backup"
  fi
else
  echo "Warning: No database client found (psql or mysqldump)"
  echo "Skipping database backup"
fi

# Backup del .env
if [ -f .env ]; then
  cp .env $BACKUP_DIR/.env.backup.$TIMESTAMP
  echo "Backed up .env file to $BACKUP_DIR"
fi

# Pull latest changes
echo "Pulling latest changes..."
git pull origin main

# Install dependencies
echo "Installing dependencies..."
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction

# Install Node dependencies and build assets - AGREGADO
echo "Installing Node dependencies..."
npm install

echo "Building assets..."
npm run build

# Crear enlace de storage - AGREGADO
echo "Creating storage link..."
php artisan storage:link

# Restaurar .env después de git pull
if [ -f $BACKUP_DIR/.env.backup.$TIMESTAMP ]; then
  cp $BACKUP_DIR/.env.backup.$TIMESTAMP .env
  echo "Restored .env from backup"
fi

# Clear caches
echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Cache config and routes for production - AGREGADO
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache

# Check if there are pending migrations
echo "Checking for pending migrations..."
PENDING_MIGRATIONS=$(php artisan migrate:status | grep -c "No" || echo "0")

if [ "$PENDING_MIGRATIONS" -gt 0 ]; then
    echo "Running migrations..."
    php artisan migrate --force
else
    echo "No pending migrations found"
fi

# Set permissions
echo "Setting permissions..."
chown -R www-data:www-data $PROJECT_DIR
chmod -R 755 $PROJECT_DIR
chmod -R 775 storage bootstrap/cache

# Restart services
echo "Restarting services..."
systemctl restart php8.3-fpm
systemctl reload nginx

echo "Deployment completed successfully!"

# Health check - ACTUALIZADO
echo "Performing health check..."
sleep 5

if curl -f https://importcbamayorista.com/api/v1/health > /dev/null 2>&1; then
    echo "Health check passed!"
elif curl -f https://importcbamayorista.com/admin > /dev/null 2>&1; then
    echo "Admin panel responding - deployment successful!"
else
    echo "Health check failed!"
    echo "Rolling back..."
    
    # Rollback: restaurar backup de BD si es necesario
    if [ -f $BACKUP_DIR/backup_before_deploy_$TIMESTAMP.sql ]; then
      echo "Restoring database backup..."
      if command -v psql &> /dev/null; then
        sudo -u postgres psql $DB_NAME < $BACKUP_DIR/backup_before_deploy_$TIMESTAMP.sql
        echo "PostgreSQL backup restored"
      elif command -v mysql &> /dev/null; then
        mysql -u root -p$MYSQL_ROOT_PASSWORD $DB_NAME < $BACKUP_DIR/backup_before_deploy_$TIMESTAMP.sql
        echo "MySQL backup restored"
      fi
    fi
    
    echo "Deployment failed and rolled back!"
    exit 1
fi

# Limpiar backups antiguos (mantener solo los últimos 7 días)
echo "Cleaning old backups..."
find $BACKUP_DIR -name "backup_before_deploy_*.sql" -mtime +7 -delete
find $BACKUP_DIR -name ".env.backup.*" -mtime +7 -delete

echo "Deployment script completed!"