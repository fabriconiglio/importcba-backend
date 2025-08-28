#!/bin/bash

# Script para verificar y configurar Git en el servidor
# Uso: ./scripts/check-git-config.sh

echo "🔍 Verificando configuración de Git..."

# Verificar si estamos en el directorio correcto
if [ ! -d ".git" ]; then
    echo "❌ Error: No se encontró el directorio .git"
    echo "   Asegúrate de ejecutar este script desde el directorio raíz del proyecto"
    exit 1
fi

# Verificar configuración actual de Git
echo "📖 Configuración actual de Git:"
echo "   Directorio de trabajo: $(pwd)"
echo "   Rama actual: $(git branch --show-current)"
echo "   Commit actual: $(git rev-parse --short HEAD)"

echo ""
echo "🔗 Configuración de remotes:"
git remote -v

echo ""
echo "🔑 Verificando acceso SSH a GitHub..."

# Verificar si SSH funciona con GitHub
if ssh -T git@github.com 2>&1 | grep -q "successfully authenticated"; then
    echo "✅ SSH a GitHub funcionando correctamente"
    SSH_WORKING=true
else
    echo "❌ SSH a GitHub no funciona"
    echo "   Error: $(ssh -T git@github.com 2>&1)"
    SSH_WORKING=false
fi

echo ""
echo "🌐 Verificando acceso HTTPS a GitHub..."

# Verificar si HTTPS funciona
if curl -s -o /dev/null -w "%{http_code}" https://github.com | grep -q "200"; then
    echo "✅ HTTPS a GitHub funcionando correctamente"
    HTTPS_WORKING=true
else
    echo "❌ HTTPS a GitHub no funciona"
    HTTPS_WORKING=false
fi

echo ""
echo "🔧 Configurando Git para deployment..."

# Agregar directorio como seguro
git config --global --add safe.directory $(pwd)
echo "✅ Directorio marcado como seguro"

# Verificar configuración de usuario
if [ -z "$(git config user.name)" ] || [ -z "$(git config user.email)" ]; then
    echo "⚠️  Configuración de usuario Git no encontrada"
    echo "   Configurando usuario por defecto..."
    git config user.name "Deployment Bot"
    git config user.email "deploy@importcba.com"
    echo "✅ Usuario Git configurado"
else
    echo "✅ Usuario Git ya configurado:"
    echo "   Nombre: $(git config user.name)"
    echo "   Email: $(git config user.email)"
fi

# Verificar y configurar remote origin
echo ""
echo "🔗 Configurando remote origin..."

if ! git remote get-url origin &> /dev/null; then
    echo "❌ No hay remote origin configurado"
    echo "   Configurando remote origin..."
    
    # Intentar detectar el repositorio desde el directorio actual
    if [ -f ".git/config" ]; then
        # Extraer información del repositorio
        REPO_URL=$(git config --get remote.origin.url 2>/dev/null || echo "")
        if [ -n "$REPO_URL" ]; then
            echo "   Remote encontrado en config: $REPO_URL"
        else
            echo "   No se pudo detectar el repositorio"
            echo "   Por favor, configura manualmente:"
            echo "   git remote add origin git@github.com:usuario/repositorio.git"
            exit 1
        fi
    fi
else
    CURRENT_REMOTE=$(git remote get-url origin)
    echo "✅ Remote origin configurado: $CURRENT_REMOTE"
    
    # Verificar si es HTTPS y convertirlo a SSH si es necesario
    if echo "$CURRENT_REMOTE" | grep -q "https://github.com"; then
        echo "🔄 Convirtiendo remote de HTTPS a SSH..."
        SSH_REMOTE=$(echo "$CURRENT_REMOTE" | sed 's|https://github.com/|git@github.com:|')
        git remote set-url origin "$SSH_REMOTE"
        echo "✅ Remote actualizado a: $SSH_REMOTE"
    elif echo "$CURRENT_REMOTE" | grep -q "git@github.com"; then
        echo "✅ Remote ya está en formato SSH"
    else
        echo "⚠️  Formato de remote no reconocido: $CURRENT_REMOTE"
    fi
fi

echo ""
echo "🧪 Probando acceso al repositorio..."

# Probar fetch
if git fetch origin main --dry-run 2>&1 | grep -q "fatal"; then
    echo "❌ Error al hacer fetch del repositorio"
    echo "   Verifica que:"
    echo "   1. El repositorio existe en GitHub"
    echo "   2. La rama 'main' existe"
    echo "   3. Tienes permisos de acceso"
    echo "   4. La clave SSH está configurada correctamente"
    
    # Mostrar información de debug
    echo ""
    echo "🔍 Información de debug:"
    echo "   Remote origin: $(git remote get-url origin)"
    echo "   Rama main existe: $(git ls-remote --heads origin main | wc -l)"
    echo "   Permisos SSH: $(ls -la ~/.ssh/ 2>/dev/null | grep -E "(id_rsa|id_ed25519)" | wc -l)"
    
    exit 1
else
    echo "✅ Fetch del repositorio exitoso"
fi

echo ""
echo "📋 Resumen de configuración Git:"
echo "   - Directorio: $(pwd)"
echo "   - Rama: $(git branch --show-current)"
echo "   - Remote: $(git remote get-url origin)"
echo "   - SSH a GitHub: $([ "$SSH_WORKING" = true ] && echo "✅" || echo "❌")"
echo "   - HTTPS a GitHub: $([ "$HTTPS_WORKING" = true ] && echo "✅" || echo "❌")"

echo ""
echo "💡 Recomendaciones:"
if [ "$SSH_WORKING" = false ]; then
    echo "   - Verificar configuración de claves SSH"
    echo "   - Agregar clave pública a GitHub"
    echo "   - Probar: ssh -T git@github.com"
fi

if echo "$(git remote get-url origin)" | grep -q "https://"; then
    echo "   - Considerar cambiar a SSH para mejor seguridad"
fi

echo "   - Ejecutar este script antes de cada deployment"
echo "   - Verificar permisos del repositorio en GitHub"

echo ""
echo "✅ Verificación de Git completada" 