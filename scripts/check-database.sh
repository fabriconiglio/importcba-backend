#!/bin/bash

# Script para verificar la configuración de la base de datos
# Uso: ./scripts/check-database.sh

echo "🔍 Verificando configuración de la base de datos..."

# Verificar si estamos en el directorio correcto
if [ ! -f ".env" ]; then
    echo "❌ Error: No se encontró el archivo .env"
    echo "   Asegúrate de ejecutar este script desde el directorio raíz del proyecto"
    exit 1
fi

# Leer configuración de la base de datos
echo "📖 Leyendo configuración desde .env..."
DB_CONNECTION=$(grep DB_CONNECTION .env | cut -d '=' -f2)
DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
DB_PORT=$(grep DB_PORT .env | cut -d '=' -f2)
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)

echo "   DB_CONNECTION: $DB_CONNECTION"
echo "   DB_HOST: $DB_HOST"
echo "   DB_PORT: $DB_PORT"
echo "   DB_DATABASE: $DB_DATABASE"
echo "   DB_USERNAME: $DB_USERNAME"

# Verificar cliente de base de datos disponible
echo ""
echo "🔧 Verificando clientes de base de datos disponibles..."

if command -v psql &> /dev/null; then
    echo "✅ PostgreSQL client (psql) encontrado"
    PSQL_VERSION=$(psql --version)
    echo "   Versión: $PSQL_VERSION"
else
    echo "❌ PostgreSQL client (psql) NO encontrado"
fi

if command -v mysql &> /dev/null; then
    echo "✅ MySQL client (mysql) encontrado"
    MYSQL_VERSION=$(mysql --version)
    echo "   Versión: $MYSQL_VERSION"
else
    echo "❌ MySQL client (mysql) NO encontrado"
fi

if command -v mysqldump &> /dev/null; then
    echo "✅ MySQL dump client (mysqldump) encontrado"
else
    echo "❌ MySQL dump client (mysqldump) NO encontrado"
fi

# Verificar conexión a la base de datos
echo ""
echo "🔌 Verificando conexión a la base de datos..."

if [ "$DB_CONNECTION" = "pgsql" ] || [ "$DB_CONNECTION" = "postgresql" ]; then
    if command -v psql &> /dev/null; then
        echo "🔄 Probando conexión PostgreSQL..."
        if PGPASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2) psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT version();" &> /dev/null; then
            echo "✅ Conexión PostgreSQL exitosa"
        else
            echo "❌ Error de conexión PostgreSQL"
            echo "   Verifica las credenciales en .env"
        fi
    else
        echo "⚠️  PostgreSQL configurado pero cliente no disponible"
    fi
elif [ "$DB_CONNECTION" = "mysql" ]; then
    if command -v mysql &> /dev/null; then
        echo "🔄 Probando conexión MySQL..."
        if mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$(grep DB_PASSWORD .env | cut -d '=' -f2)" "$DB_DATABASE" -e "SELECT VERSION();" &> /dev/null; then
            echo "✅ Conexión MySQL exitosa"
        else
            echo "❌ Error de conexión MySQL"
            echo "   Verifica las credenciales en .env"
        fi
    else
        echo "⚠️  MySQL configurado pero cliente no disponible"
    fi
else
    echo "⚠️  Tipo de conexión no reconocido: $DB_CONNECTION"
fi

# Verificar si Laravel puede conectarse
echo ""
echo "🎯 Verificando conexión desde Laravel..."
if php artisan tinker --execute="echo 'Laravel DB connection: ' . (DB::connection()->getPdo() ? 'OK' : 'FAILED');" 2>/dev/null; then
    echo "✅ Laravel puede conectarse a la base de datos"
else
    echo "❌ Laravel no puede conectarse a la base de datos"
    echo "   Ejecuta: php artisan config:clear"
    echo "   Verifica: php artisan migrate:status"
fi

echo ""
echo "📋 Resumen de verificación:"
echo "   - Tipo de BD configurado: $DB_CONNECTION"
echo "   - Base de datos: $DB_DATABASE"
echo "   - Host: $DB_HOST:$DB_PORT"

if [ "$DB_CONNECTION" = "pgsql" ] || [ "$DB_CONNECTION" = "postgresql" ]; then
    if command -v psql &> /dev/null; then
        echo "   - Cliente PostgreSQL: ✅ Disponible"
    else
        echo "   - Cliente PostgreSQL: ❌ No disponible"
    fi
elif [ "$DB_CONNECTION" = "mysql" ]; then
    if command -v mysql &> /dev/null; then
        echo "   - Cliente MySQL: ✅ Disponible"
    else
        echo "   - Cliente MySQL: ❌ No disponible"
    fi
fi

echo ""
echo "💡 Recomendaciones:"
if [ "$DB_CONNECTION" = "pgsql" ] || [ "$DB_CONNECTION" = "postgresql" ]; then
    if ! command -v psql &> /dev/null; then
        echo "   - Instalar cliente PostgreSQL: sudo apt-get install postgresql-client"
    fi
elif [ "$DB_CONNECTION" = "mysql" ]; then
    if ! command -v mysql &> /dev/null; then
        echo "   - Instalar cliente MySQL: sudo apt-get install mysql-client"
    fi
fi

echo "   - Verificar que el usuario de BD tenga permisos de backup"
echo "   - Probar el script de deployment localmente antes de usar GitHub Actions" 