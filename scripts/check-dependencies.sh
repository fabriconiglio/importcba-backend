#!/bin/bash

# Script para verificar dependencias antes del deployment
# Uso: ./scripts/check-dependencies.sh

echo "🔍 Verificando dependencias del sistema..."

# Verificar si estamos en el directorio correcto
if [ ! -f "composer.json" ]; then
    echo "❌ Error: No se encontró composer.json"
    echo "   Asegúrate de ejecutar este script desde el directorio raíz del proyecto"
    exit 1
fi

echo "📦 Verificando dependencias de PHP..."

# Verificar Composer
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version | head -n1)
    echo "✅ Composer encontrado: $COMPOSER_VERSION"
else
    echo "❌ Composer NO encontrado"
    echo "   Instalar: curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer"
    exit 1
fi

# Verificar PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php --version | head -n1)
    echo "✅ PHP encontrado: $PHP_VERSION"
    
    # Verificar extensión requeridas
    REQUIRED_EXTENSIONS=("pdo" "pdo_pgsql" "pdo_mysql" "mbstring" "xml" "curl" "gd" "zip")
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -q "^$ext$"; then
            echo "   ✅ Extensión $ext disponible"
        else
            echo "   ❌ Extensión $ext NO disponible"
        fi
    done
else
    echo "❌ PHP NO encontrado"
    exit 1
fi

echo ""
echo "🌐 Verificando dependencias de Node.js..."

# Verificar Node.js
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    echo "✅ Node.js encontrado: $NODE_VERSION"
else
    echo "❌ Node.js NO encontrado"
    echo "   Instalar: curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash - && sudo apt-get install -y nodejs"
    exit 1
fi

# Verificar npm
if command -v npm &> /dev/null; then
    NPM_VERSION=$(npm --version)
    echo "✅ npm encontrado: $NPM_VERSION"
else
    echo "❌ npm NO encontrado"
    exit 1
fi

# Verificar Vite
if [ -f "node_modules/.bin/vite" ]; then
    echo "✅ Vite disponible en node_modules"
elif command -v vite &> /dev/null; then
    VITE_VERSION=$(vite --version)
    echo "✅ Vite global encontrado: $VITE_VERSION"
else
    echo "⚠️  Vite no encontrado - se instalará con npm install"
fi

echo ""
echo "🗄️  Verificando dependencias de base de datos..."

# Verificar PostgreSQL
if command -v psql &> /dev/null; then
    PSQL_VERSION=$(psql --version | head -n1)
    echo "✅ PostgreSQL client encontrado: $PSQL_VERSION"
else
    echo "❌ PostgreSQL client NO encontrado"
    echo "   Instalar: sudo apt-get install postgresql-client"
fi

# Verificar MySQL
if command -v mysql &> /dev/null; then
    MYSQL_VERSION=$(mysql --version | head -n1)
    echo "✅ MySQL client encontrado: $MYSQL_VERSION"
else
    echo "❌ MySQL client NO encontrado"
    echo "   Instalar: sudo apt-get install mysql-client"
fi

echo ""
echo "🔧 Verificando dependencias del sistema..."

# Verificar Git
if command -v git &> /dev/null; then
    GIT_VERSION=$(git --version | head -n1)
    echo "✅ Git encontrado: $GIT_VERSION"
else
    echo "❌ Git NO encontrado"
    echo "   Instalar: sudo apt-get install git"
    exit 1
fi

# Verificar curl
if command -v curl &> /dev/null; then
    CURL_VERSION=$(curl --version | head -n1)
    echo "✅ curl encontrado: $CURL_VERSION"
else
    echo "❌ curl NO encontrado"
    echo "   Instalar: sudo apt-get install curl"
fi

# Verificar permisos de directorios
echo ""
echo "📁 Verificando permisos de directorios..."

CRITICAL_DIRS=("storage" "bootstrap/cache" "public")
for dir in "${CRITICAL_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        if [ -w "$dir" ]; then
            echo "✅ $dir: escribible"
        else
            echo "❌ $dir: NO escribible"
            echo "   Corregir: sudo chmod -R 775 $dir"
        fi
    else
        echo "⚠️  $dir: no existe"
    fi
done

echo ""
echo "🧪 Verificando archivos de configuración..."

# Verificar .env
if [ -f ".env" ]; then
    echo "✅ Archivo .env encontrado"
    
    # Verificar variables críticas
    REQUIRED_VARS=("DB_CONNECTION" "DB_HOST" "DB_DATABASE" "APP_KEY")
    for var in "${REQUIRED_VARS[@]}"; do
        if grep -q "^$var=" .env; then
            echo "   ✅ $var configurado"
        else
            echo "   ❌ $var NO configurado"
        fi
    done
else
    echo "⚠️  Archivo .env NO encontrado"
    if [ -f ".env.example" ]; then
        echo "   ✅ .env.example disponible - se puede copiar"
    else
        echo "   ❌ .env.example NO disponible"
    fi
fi

# Verificar composer.json
if [ -f "composer.json" ]; then
    echo "✅ composer.json encontrado"
else
    echo "❌ composer.json NO encontrado"
    exit 1
fi

# Verificar package.json
if [ -f "package.json" ]; then
    echo "✅ package.json encontrado"
    
    # Verificar scripts disponibles
    if grep -q '"build"' package.json; then
        echo "   ✅ Script build disponible"
    else
        echo "   ❌ Script build NO disponible"
    fi
else
    echo "❌ package.json NO encontrado"
fi

echo ""
echo "📋 Resumen de verificación:"
echo "   - PHP: ✅ Disponible"
echo "   - Composer: ✅ Disponible"
echo "   - Node.js: ✅ Disponible"
echo "   - npm: ✅ Disponible"
echo "   - Git: ✅ Disponible"
echo "   - Base de datos: $([ -f ".env" ] && echo "✅ Configurado" || echo "⚠️  Por configurar")"

echo ""
echo "💡 Recomendaciones:"
echo "   - Ejecutar este script antes de cada deployment"
echo "   - Verificar que todas las extensiones PHP estén habilitadas"
echo "   - Asegurar que los directorios críticos tengan permisos correctos"
echo "   - Verificar que .env tenga todas las variables requeridas"

echo ""
echo "✅ Verificación de dependencias completada" 