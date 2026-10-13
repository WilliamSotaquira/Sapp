#!/usr/bin/env bash
#
# deploy.sh — Despliegue idempotente de Sapp en producción
#
# Uso (ya conectado al servidor como el usuario del sitio):
#   cd ~/htdocs/sapp.weirdoware.com
#   bash scripts/deploy.sh
#
# O en remoto desde tu máquina (ver scripts/README-deploy.md):
#   ssh sapp-prod 'cd ~/htdocs/sapp.weirdoware.com && bash scripts/deploy.sh'
#
# El script es seguro de repetir: si algo falla, aborta y deja indicaciones de rollback.

set -Eeuo pipefail

# ─────────────────────────────────────────────────────────────
# Configuración (ajusta aquí si cambia el entorno)
# ─────────────────────────────────────────────────────────────
PROJECT_DIR="${PROJECT_DIR:-$HOME/htdocs/sapp.weirdoware.com}"
DB_USER="${DB_USER:-weirdoware_sapp}"
DB_NAME="${DB_NAME:-weirdoware_sapp}"
BACKUP_DIR="${BACKUP_DIR:-$HOME/backups}"
GIT_BRANCH="${GIT_BRANCH:-main}"
BUILD_ASSETS="${BUILD_ASSETS:-1}"   # 1 = correr npm ci && npm run build en el server
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.4-fpm}"   # servicio php-fpm del sitio (CloudPanel)

# ─────────────────────────────────────────────────────────────
# Utilidades
# ─────────────────────────────────────────────────────────────
log()  { printf '\033[1;34m[deploy]\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m[  ok  ]\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m[ warn ]\033[0m %s\n' "$*"; }
err()  { printf '\033[1;31m[error ]\033[0m %s\n' "$*" >&2; }

PREV_COMMIT=""

on_error() {
  err "El despliegue falló. La aplicación puede estar en modo mantenimiento."
  if [[ -n "$PREV_COMMIT" ]]; then
    err "Para revertir el código al estado anterior ejecuta:"
    err "    git reset --hard $PREV_COMMIT"
    err "    php artisan optimize:clear && php artisan optimize && php artisan up"
  fi
  err "Si fuera necesario restaurar la BD, usa el backup más reciente en: $BACKUP_DIR"
  err "    mysql -u $DB_USER -p $DB_NAME < $BACKUP_DIR/<archivo>.sql"
  exit 1
}
trap on_error ERR

# ─────────────────────────────────────────────────────────────
# 0. Comprobaciones previas
# ─────────────────────────────────────────────────────────────
cd "$PROJECT_DIR"
log "Directorio del proyecto: $PROJECT_DIR"

command -v php      >/dev/null || { err "php no está en el PATH"; exit 1; }
command -v composer >/dev/null || { err "composer no está en el PATH"; exit 1; }
command -v git      >/dev/null || { err "git no está en el PATH"; exit 1; }
[[ -f artisan ]] || { err "No se encontró 'artisan'. ¿Estás en la raíz del proyecto?"; exit 1; }

PREV_COMMIT="$(git rev-parse HEAD)"
log "Commit actual: $PREV_COMMIT"

# ─────────────────────────────────────────────────────────────
# 1. Backup de la base de datos
# ─────────────────────────────────────────────────────────────
mkdir -p "$BACKUP_DIR"
BACKUP_FILE="$BACKUP_DIR/sapp_$(date +%Y%m%d_%H%M%S).sql"
log "Creando backup de BD en $BACKUP_FILE"
# -p sin valor pedirá la contraseña de forma interactiva; usa ~/.my.cnf para evitarlo.
mysqldump -u "$DB_USER" -p "$DB_NAME" > "$BACKUP_FILE"
ok "Backup creado ($(du -h "$BACKUP_FILE" | cut -f1))"

# ─────────────────────────────────────────────────────────────
# 2. Modo mantenimiento
# ─────────────────────────────────────────────────────────────
log "Activando modo mantenimiento"
php artisan down --retry=60 || warn "La app ya estaba en mantenimiento"

# ─────────────────────────────────────────────────────────────
# 3. Actualizar código
# ─────────────────────────────────────────────────────────────
log "Descargando últimos cambios (origin/$GIT_BRANCH)"
git fetch origin "$GIT_BRANCH"
git reset --hard "origin/$GIT_BRANCH"
NEW_COMMIT="$(git rev-parse HEAD)"
log "Nuevo commit: $NEW_COMMIT"

# ─────────────────────────────────────────────────────────────
# 4. Dependencias PHP
# ─────────────────────────────────────────────────────────────
log "Instalando dependencias de Composer (sin dev, optimizado)"
composer install --no-dev --optimize-autoloader --no-interaction

# ─────────────────────────────────────────────────────────────
# 5. Assets (Vite/Tailwind) — solo si hay Node en el server
# ─────────────────────────────────────────────────────────────
if [[ "$BUILD_ASSETS" == "1" ]] && command -v npm >/dev/null; then
  log "Compilando assets con npm"
  npm ci
  npm run build
else
  warn "Saltando build de assets (BUILD_ASSETS=$BUILD_ASSETS o npm no disponible)"
fi

# ─────────────────────────────────────────────────────────────
# 6. Migraciones
# ─────────────────────────────────────────────────────────────
log "Ejecutando migraciones"
php artisan migrate --force

# ─────────────────────────────────────────────────────────────
# 7. Optimización de cachés
# ─────────────────────────────────────────────────────────────
log "Limpiando y regenerando cachés"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ─────────────────────────────────────────────────────────────
# 8. Reiniciar workers de cola
# ─────────────────────────────────────────────────────────────
log "Reiniciando queue workers"
php artisan queue:restart || warn "No se pudo reiniciar la cola (¿no hay workers?)"

# ─────────────────────────────────────────────────────────────
# 8b. Recargar php-fpm (opcional, solo si hay sudo sin contraseña)
#     En CloudPanel el usuario del sitio normalmente NO puede reiniciar
#     servicios: en ese caso se avisa y el deploy continúa sin fallar.
#     Para un deploy normal de Laravel esto casi nunca es necesario
#     (optimize:clear + config:cache ya refrescan la app).
# ─────────────────────────────────────────────────────────────
if sudo -n systemctl reload "$PHP_FPM_SERVICE" >/dev/null 2>&1; then
  ok "php-fpm ($PHP_FPM_SERVICE) recargado"
else
  warn "No se recargó $PHP_FPM_SERVICE (sin permiso sudo). Normalmente no hace falta."
  warn "Si lo necesitas, ejecútalo con tu usuario admin:"
  warn "    sudo systemctl reload $PHP_FPM_SERVICE"
fi

# ─────────────────────────────────────────────────────────────
# 9. Salir de mantenimiento
# ─────────────────────────────────────────────────────────────
log "Desactivando modo mantenimiento"
php artisan up

# ─────────────────────────────────────────────────────────────
# 10. Verificación básica
# ─────────────────────────────────────────────────────────────
ok "Despliegue completado."
log "Commit desplegado: $NEW_COMMIT"
log "Revisa los logs si observas algo raro:"
log "    tail -n 50 storage/logs/laravel.log"

trap - ERR
