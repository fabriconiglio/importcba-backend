#!/bin/bash

# Script de despliegue para el backend
# Uso: ./deploy-script.sh

set -e  # Salir si hay algún error

echo "🚀 Starting deployment..."

# Variables
PROJECT_DIR="/var/www/ecommerce-backend"
BACKUP_DIR="/var/www/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Crear directorio de backups si no existe
mkdir -p $BACKUP_DIR

# Navegar al directorio del proyecto
cd $PROJECT_DIR

# Backup de la base de datos antes del despliegue
echo "💾 Creating database backup..."
sudo -u postgres pg_dump ecommerce_import > $BACKUP_DIR/backup_before_deploy_$TIMESTAMP.sql

# Pull latest changes
echo "📥 Pulling latest changes..."
git pull origin main

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Check if there are pending migrations
echo "🔍 Checking for pending migrations..."
PENDING_MIGRATIONS=$(php artisan migrate:status | grep -c "No" || echo "0")

if [ "$PENDING_MIGRATIONS" -gt 0 ]; then
    echo "🔄 Running migrations..."
    php artisan migrate --force
else
    echo "✅ No pending migrations found"
fi

# Set permissions
echo "🔐 Setting permissions..."
chown -R www-data:www-data $PROJECT_DIR
chmod -R 755 $PROJECT_DIR
chmod -R 775 storage bootstrap/cache

# Restart services
echo "🔄 Restarting services..."
systemctl restart php8.3-fpm
systemctl reload nginx

echo "✅ Deployment completed successfully!"

# Health check
echo "🏥 Performing health check..."
sleep 5  # Esperar un poco para que los servicios se reinicien

if curl -f http://localhost/api/v1/health > /dev/null 2>&1; then
    echo "✅ Health check passed!"
    echo "🎉 Deployment successful!"
else
    echo "❌ Health check failed!"
    echo "🔧 Rolling back..."
    
    # Rollback: restaurar backup de BD si es necesario
    echo "🔄 Restoring database backup..."
    sudo -u postgres psql ecommerce_import < $BACKUP_DIR/backup_before_deploy_$TIMESTAMP.sql
    
    echo "❌ Deployment failed and rolled back!"
    exit 1
fi

# Limpiar backups antiguos (mantener solo los últimos 5)
echo "🧹 Cleaning old backups..."
ls -t $BACKUP_DIR/backup_before_deploy_*.sql | tail -n +6 | xargs -r rm

echo "🎯 Deployment script completed!" 