#!/usr/bin/env bash
#
# deploy.sh — Despliegue de SAPP en producción (CloudPanel + MySQL + Cloudflare R2)
#
# Sigue el "Instructivo de despliegue, migraciones y respaldo" (Rev. 2, oct 2026).
#
# Uso (ya conectado al servidor como el usuario del sitio):
#   sudo -iu weirdoware-sapp
#   cd ~/htdocs/sapp.weirdoware.com
#   bash scripts/deploy.sh
#
# REGLA PRINCIPAL: nunca ejecutar 'migrate --force' sin un backup verificado
# (/usr/local/bin/backup-sapp.sh) confirmado en Cloudflare R2.
#
# El script se DETIENE y pide confirmación en los puntos críticos (backup y
# migraciones) a propósito: producción exige criterio humano, no automatización
# a ciegas. Para un deploy totalmente desatendido, ver la variable ASSUME_YES
# (NO recomendado cuando hay migraciones).

set -Eeuo pipefail

# ─────────────────────────────────────────────────────────────
# Configuración (ajusta aquí si cambia el entorno)
# ─────────────────────────────────────────────────────────────
PROJECT_DIR="${PROJECT_DIR:-/home/weirdoware-sapp/htdocs/sapp.weirdoware.com}"
GIT_BRANCH="${GIT_BRANCH:-main}"
BUILD_ASSETS="${BUILD_ASSETS:-auto}"       # auto = compila solo si cambió el frontend; 1 = siempre; 0 = nunca
BACKUP_SCRIPT="${BACKUP_SCRIPT:-/usr/local/bin/backup-sapp.sh}"
BACKUP_OK_MSG="${BACKUP_OK_MSG:-BACKUP SAPP COMPLETADO CORRECTAMENTE}"
LOCAL_BACKUP_DIR="${LOCAL_BACKUP_DIR:-/var/backups/sapp}"
R2_DB_PATH="${R2_DB_PATH:-weirdoware-r2:weirdoware-backups/sapp/database}"
R2_STORAGE_PATH="${R2_STORAGE_PATH:-weirdoware-r2:weirdoware-backups/sapp/storage}"
QUEUE_SERVICE="${QUEUE_SERVICE:-sapp-queue.service}"
ASSUME_YES="${ASSUME_YES:-0}"              # 1 = no preguntar (solo si NO hay migraciones)

# ─────────────────────────────────────────────────────────────
# Utilidades
# ─────────────────────────────────────────────────────────────
log()  { printf '\033[1;34m[deploy]\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m[  ok  ]\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m[ warn ]\033[0m %s\n' "$*"; }
err()  { printf '\033[1;31m[error ]\033[0m %s\n' "$*" >&2; }

confirm() {
  # confirm "pregunta" -> 0 si el usuario acepta, 1 si no
  [[ "$ASSUME_YES" == "1" ]] && return 0
  local reply
  read -r -p "$(printf '\033[1;35m[?]\033[0m %s [s/N] ' "$1")" reply
  [[ "$reply" =~ ^[sSyY]$ ]]
}

PREV_COMMIT=""

on_error() {
  err "El despliegue falló. La aplicación puede haber quedado en modo mantenimiento."
  if [[ -n "$PREV_COMMIT" ]]; then
    err "Para volver el código al commit anterior:"
    err "    git reset --hard $PREV_COMMIT   # ojo: descarta cambios traidos"
    err "    php artisan config:cache && php artisan view:cache && php artisan up"
  fi
  err "Si una migración dejó la BD inconsistente, NO improvises: restaura desde el"
  err "backup previo. Backups locales en $LOCAL_BACKUP_DIR y en R2 ($R2_DB_PATH)."
  err "Consulta la sección 8 y 9 del instructivo."
  exit 1
}
trap on_error ERR

# ─────────────────────────────────────────────────────────────
# 0. Comprobaciones previas
# ─────────────────────────────────────────────────────────────
cd "$PROJECT_DIR"
log "Directorio del proyecto: $(pwd)"

command -v php      >/dev/null || { err "php no está en el PATH"; exit 1; }
command -v composer >/dev/null || { err "composer no está en el PATH"; exit 1; }
command -v git      >/dev/null || { err "git no está en el PATH"; exit 1; }
[[ -f artisan ]] || { err "No se encontró 'artisan'. ¿Estás en la raíz del proyecto?"; exit 1; }

PREV_COMMIT="$(git rev-parse HEAD)"
log "Commit actual: $PREV_COMMIT"

# ─────────────────────────────────────────────────────────────
# 1. Revisar estado del repositorio (instructivo §3.1)
# ─────────────────────────────────────────────────────────────
log "Estado del repositorio:"
git status --short
if [[ -n "$(git status --porcelain)" ]]; then
  warn "Hay cambios locales sin confirmar en el working tree."
  confirm "¿Continuar de todas formas (se mantendrá un 'git pull', no un reset)?" \
    || { err "Abortado por el usuario para revisar cambios locales."; exit 1; }
fi

# ─────────────────────────────────────────────────────────────
# 2. Traer los cambios (instructivo §3.2) — pull, NO reset --hard
# ─────────────────────────────────────────────────────────────
log "Trayendo cambios de origin/$GIT_BRANCH"
git pull origin "$GIT_BRANCH"
NEW_COMMIT="$(git rev-parse HEAD)"
log "Commit tras pull: $NEW_COMMIT"

# ─────────────────────────────────────────────────────────────
# 3. Dependencias PHP (instructivo §3.3)
# ─────────────────────────────────────────────────────────────
log "Instalando dependencias de Composer (sin dev, optimizado)"
composer install --no-dev --optimize-autoloader --no-interaction

# ─────────────────────────────────────────────────────────────
# 4. Assets front (instructivo §3.4) — solo si cambió el frontend
# ─────────────────────────────────────────────────────────────
FRONT_CHANGED=0
if git diff --name-only "$PREV_COMMIT" "$NEW_COMMIT" 2>/dev/null \
     | grep -qE '^(resources/|package(-lock)?\.json|vite\.config\.js|tailwind\.config\.|postcss)'; then
  FRONT_CHANGED=1
fi
if [[ "$BUILD_ASSETS" == "1" ]] || { [[ "$BUILD_ASSETS" == "auto" ]] && [[ "$FRONT_CHANGED" == "1" ]]; }; then
  if command -v npm >/dev/null; then
    log "Compilando assets (cambios de frontend detectados o forzado)"
    npm install
    npm run build
  else
    warn "npm no disponible: saltando build de assets."
  fi
else
  log "Sin cambios de frontend: no se recompilan assets."
fi

# ─────────────────────────────────────────────────────────────
# 5. Revisar migraciones (instructivo §3.5 y §3.6)
# ─────────────────────────────────────────────────────────────
log "Estado de migraciones (migrate:status):"
php artisan migrate:status || true

# Detectar si hay migraciones pendientes
PENDING_MIGRATIONS=0
if php artisan migrate:status 2>/dev/null | grep -qiE '\bPending\b|\bNo\b'; then
  PENDING_MIGRATIONS=1
fi

if [[ "$PENDING_MIGRATIONS" == "1" ]]; then
  warn "Hay migraciones PENDIENTES."
  log "SQL previsto (migrate --pretend, no modifica la base):"
  php artisan migrate --pretend || true

  echo
  warn "REGLA PRINCIPAL: no se migra sin backup verificado en R2."
  warn "Revisa el SQL de arriba y clasifica el riesgo (instructivo §5)."
  warn "Comandos destructivos (dropColumn/dropTable/cambios masivos) exigen"
  warn "ventana controlada y plan de restauración probado."
  echo

  # ── 6. Backup obligatorio antes de migrar (instructivo §4) ──
  if [[ ! -x "$BACKUP_SCRIPT" ]]; then
    err "No se encontró el script de backup ejecutable: $BACKUP_SCRIPT"
    err "Abortando: no se migra sin backup."
    exit 1
  fi
  confirm "¿Ejecutar backup completo ($BACKUP_SCRIPT) ahora?" \
    || { err "Abortado: no se migra sin backup."; exit 1; }

  log "Ejecutando backup completo..."
  BACKUP_OUTPUT="$("$BACKUP_SCRIPT" 2>&1)"
  echo "$BACKUP_OUTPUT"
  if ! grep -qF "$BACKUP_OK_MSG" <<<"$BACKUP_OUTPUT"; then
    err "El backup NO terminó con el mensaje de éxito esperado:"
    err "    \"$BACKUP_OK_MSG\""
    err "Abortando antes de migrar (instructivo §4)."
    exit 1
  fi
  ok "Backup completado correctamente."

  log "Verificando archivos locales en $LOCAL_BACKUP_DIR:"
  ls -lh "$LOCAL_BACKUP_DIR" || warn "No se pudo listar $LOCAL_BACKUP_DIR"
  if command -v rclone >/dev/null; then
    log "Verificando backups de BD en R2:"
    rclone lsf "$R2_DB_PATH" || warn "No se pudo listar $R2_DB_PATH"
    log "Verificando backups de storage en R2:"
    rclone lsf "$R2_STORAGE_PATH" || warn "No se pudo listar $R2_STORAGE_PATH"
  else
    warn "rclone no disponible en PATH: verifica manualmente los backups en R2."
  fi

  echo
  confirm "¿Confirmas que el backup está en R2 y quieres EJECUTAR las migraciones (migrate --force)?" \
    || { err "Abortado: migraciones no ejecutadas. El código ya está actualizado."; exit 1; }

  log "Ejecutando migraciones (migrate --force)"
  php artisan migrate --force
  ok "Migraciones aplicadas."
else
  ok "No hay migraciones pendientes: no se requiere backup para este deploy."
fi

# ─────────────────────────────────────────────────────────────
# 7. Regenerar cachés (instructivo §3.9) — config + view (sin route:cache)
# ─────────────────────────────────────────────────────────────
log "Regenerando cachés (config, view)"
php artisan config:cache
php artisan view:cache

# ─────────────────────────────────────────────────────────────
# 8. Reiniciar worker de cola (instructivo §3.10)
#    queue:restart avisa a los workers; además se reinicia el servicio systemd
#    si el usuario tiene permiso sudo (si no, se avisa sin romper el deploy).
# ─────────────────────────────────────────────────────────────
log "Señalando reinicio de la cola (queue:restart)"
php artisan queue:restart || warn "queue:restart falló (¿sin workers activos?)"

if sudo -n systemctl restart "$QUEUE_SERVICE" >/dev/null 2>&1; then
  ok "Servicio de cola reiniciado ($QUEUE_SERVICE)"
else
  warn "No se reinició $QUEUE_SERVICE vía sudo (sin permiso). Si hace falta, ejecútalo"
  warn "con tu usuario admin: sudo systemctl restart $QUEUE_SERVICE"
fi

# ─────────────────────────────────────────────────────────────
# 9. Verificación posterior (instructivo §7)
# ─────────────────────────────────────────────────────────────
ok "Despliegue completado."
log "Commit desplegado: $NEW_COMMIT"
echo
log "Verificación recomendada (instructivo §7):"
log "  - Abrir https://sapp.weirdoware.com e iniciar sesión."
log "  - Revisar dashboard, una solicitud, una evidencia y reportes."
log "  - Jobs/colas:"
log "      php artisan tinker --execute='echo \"Pendientes: \".DB::table(\"jobs\")->count().PHP_EOL; echo \"Fallidos: \".DB::table(\"failed_jobs\")->count().PHP_EOL;'"
log "      sudo systemctl status $QUEUE_SERVICE --no-pager"
log "  - Logs: tail -n 100 storage/logs/laravel.log"

trap - ERR
