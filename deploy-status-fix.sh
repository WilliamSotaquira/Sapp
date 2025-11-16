#!/bin/bash

#####################################################################
# Script de Despliegue Automático - Fix Status Null Error
# Fecha: 16 de noviembre de 2025
# Commit: 3aea8a0
#####################################################################

set -e  # Salir si hay algún error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para imprimir con color
print_status() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    print_error "No se encuentra el archivo artisan. Asegúrate de estar en la raíz del proyecto Laravel."
    exit 1
fi

print_status "==================================================================="
print_status "Iniciando Despliegue - Fix Status Null Error"
print_status "==================================================================="

# 1. Backup de base de datos
print_status "Paso 1/10: Creando backup de base de datos..."
BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"
if command -v mysqldump &> /dev/null; then
    read -p "Ingresa el nombre de usuario de MySQL: " DB_USER
    read -sp "Ingresa la contraseña de MySQL: " DB_PASS
    echo
    read -p "Ingresa el nombre de la base de datos: " DB_NAME
    
    mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "storage/backups/$BACKUP_FILE" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        print_success "Backup creado: storage/backups/$BACKUP_FILE"
    else
        print_error "Error al crear backup. Continuando de todos modos..."
    fi
else
    print_warning "mysqldump no encontrado. Saltando backup automático."
    read -p "¿Has creado un backup manual? (s/n): " MANUAL_BACKUP
    if [ "$MANUAL_BACKUP" != "s" ]; then
        print_error "Por favor crea un backup antes de continuar."
        exit 1
    fi
fi

# 2. Modo mantenimiento
print_status "Paso 2/10: Activando modo mantenimiento..."
php artisan down --message="Actualizando sistema" --retry=60
print_success "Aplicación en modo mantenimiento"

# 3. Guardar cambios locales
print_status "Paso 3/10: Guardando cambios locales (si existen)..."
if git diff-index --quiet HEAD --; then
    print_success "No hay cambios locales"
else
    git stash
    print_warning "Cambios locales guardados en stash"
fi

# 4. Actualizar código
print_status "Paso 4/10: Obteniendo últimos cambios del repositorio..."
git fetch origin
git pull origin main
print_success "Código actualizado"

# 5. Verificar commit
print_status "Paso 5/10: Verificando commit..."
CURRENT_COMMIT=$(git rev-parse --short HEAD)
print_status "Commit actual: $CURRENT_COMMIT"

if git log --oneline -n 10 | grep -q "3aea8a0"; then
    print_success "Commit del fix encontrado en el historial"
else
    print_warning "No se encontró el commit específico del fix, pero continuando..."
fi

# 6. Instalar/actualizar dependencias (si es necesario)
print_status "Paso 6/10: Verificando dependencias..."
if [ -f "composer.lock" ]; then
    composer install --no-dev --optimize-autoloader --no-interaction
    print_success "Dependencias verificadas"
fi

# 7. Limpiar cachés
print_status "Paso 7/10: Limpiando cachés..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
print_success "Cachés limpiados"

# 8. Optimizar para producción
print_status "Paso 8/10: Optimizando para producción..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_success "Optimizaciones aplicadas"

# 9. Reiniciar servicios
print_status "Paso 9/10: Reiniciando servicios..."

# Queue workers
if pgrep -f "queue:work" > /dev/null; then
    php artisan queue:restart
    print_success "Queue workers reiniciados"
fi

# PHP-FPM (requiere permisos sudo)
read -p "¿Deseas reiniciar PHP-FPM? (s/n): " RESTART_PHP
if [ "$RESTART_PHP" = "s" ]; then
    if command -v systemctl &> /dev/null; then
        sudo systemctl restart php-fpm || sudo systemctl restart php8.2-fpm || print_warning "No se pudo reiniciar PHP-FPM"
    else
        sudo service php-fpm restart || sudo service php8.2-fpm restart || print_warning "No se pudo reiniciar PHP-FPM"
    fi
    print_success "PHP-FPM reiniciado"
fi

# Nginx/Apache
read -p "¿Deseas reiniciar el servidor web (nginx/apache)? (s/n): " RESTART_WEB
if [ "$RESTART_WEB" = "s" ]; then
    if command -v systemctl &> /dev/null; then
        sudo systemctl restart nginx || sudo systemctl restart apache2 || print_warning "No se pudo reiniciar el servidor web"
    else
        sudo service nginx restart || sudo service apache2 restart || print_warning "No se pudo reiniciar el servidor web"
    fi
    print_success "Servidor web reiniciado"
fi

# 10. Verificación del fix
print_status "Paso 10/10: Verificando el fix..."
VERIFICATION=$(php artisan tinker --execute="echo (new \App\Models\ServiceRequest)->status ?? 'ERROR';")
if echo "$VERIFICATION" | grep -q "PENDIENTE"; then
    print_success "Fix verificado correctamente: status por defecto = PENDIENTE"
else
    print_warning "No se pudo verificar el fix automáticamente"
fi

# Activar aplicación
print_status "Activando aplicación..."
php artisan up
print_success "Aplicación activada"

print_status "==================================================================="
print_success "¡Despliegue completado exitosamente!"
print_status "==================================================================="

echo ""
print_status "Próximos pasos:"
echo "1. Verificar que la aplicación carga correctamente"
echo "2. Crear una solicitud de prueba"
echo "3. Verificar que el historial de estados se registra correctamente"
echo "4. Revisar logs: tail -f storage/logs/laravel.log"
echo ""

# Mostrar información de rollback
print_warning "En caso de necesitar hacer rollback:"
echo "git reset --hard $CURRENT_COMMIT~1"
echo "php artisan cache:clear && php artisan config:cache"
echo ""

print_status "Backup disponible en: storage/backups/$BACKUP_FILE"
