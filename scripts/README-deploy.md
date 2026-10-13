# Despliegue de SAPP

Despliegue en producción (CloudPanel + MySQL + Cloudflare R2). Un solo script
(`scripts/deploy.sh`) implementa el flujo del **Instructivo de despliegue,
migraciones y respaldo** (Rev. 2, oct 2026).

> REGLA PRINCIPAL: nunca se ejecuta `migrate --force` sin un backup verificado
> (`/usr/local/bin/backup-sapp.sh`) confirmado en Cloudflare R2. El script se
> detiene y pide confirmación en los puntos críticos a propósito.

## 1. Configurar el acceso SSH (una sola vez, en tu máquina)

Conexión por Tailscale/SSH. Añade este bloque a tu `~/.ssh/config` (en Windows:
`C:\Users\willi\.ssh\config`):

```sshconfig
Host sapp-prod
    HostName 100.84.78.120
    User william-sora
    IdentityFile ~/.ssh/id_ed25519   # ajusta al nombre de tu clave
    IdentitiesOnly yes
```

Luego te conectas con `ssh sapp-prod`.

> El despliegue corre como el usuario del sitio. Tras conectarte:
> `sudo -iu weirdoware-sapp` y luego `cd ~/htdocs/sapp.weirdoware.com`.
> Confirma el prompt: `weirdoware-sapp@qces-wing:~/htdocs/sapp.weirdoware.com$`

## 2. Desplegar

```bash
ssh sapp-prod
sudo -iu weirdoware-sapp
cd ~/htdocs/sapp.weirdoware.com
bash scripts/deploy.sh
```

> Primera vez: el servidor aún no tiene este `deploy.sh` hasta el primer `git pull`.
> Haz un `git pull origin main` manual esa primera vez; a partir de ahí el script
> se encarga del pull en cada deploy.

### Qué hace el script, en orden

1. Muestra `git status`; si hay cambios locales, pide confirmación.
2. `git pull origin main` (NO `reset --hard`: no descarta tu trabajo sin avisar).
3. `composer install --no-dev --optimize-autoloader`.
4. Compila assets **solo si cambió el frontend** (`resources/`, `package.json`,
   `vite.config.js`, etc.). Forzable con `BUILD_ASSETS=1`.
5. `migrate:status` y, si hay migraciones pendientes:
   - muestra `migrate --pretend` (SQL previsto),
   - **exige** ejecutar `/usr/local/bin/backup-sapp.sh` y verifica el mensaje
     `BACKUP SAPP COMPLETADO CORRECTAMENTE`,
   - lista los backups locales y en R2 (`rclone lsf`),
   - pide confirmación final y recién entonces `migrate --force`.
   Si NO hay migraciones pendientes, se salta backup y migración.
6. `config:cache` + `view:cache` (no `route:cache`, según instructivo).
7. `queue:restart` y, si hay sudo, `systemctl restart sapp-queue.service`.
8. Imprime la checklist de verificación posterior (§7 del instructivo).

Si algo falla, aborta y recuerda que ante inconsistencia de datos hay que
**restaurar desde el backup**, no improvisar sobre producción (§8–§9).

## 3. Variables ajustables

| Variable          | Por defecto                                             |
|-------------------|---------------------------------------------------------|
| `PROJECT_DIR`     | `/home/weirdoware-sapp/htdocs/sapp.weirdoware.com`      |
| `GIT_BRANCH`      | `main`                                                  |
| `BUILD_ASSETS`    | `auto` (`1` = siempre, `0` = nunca)                     |
| `BACKUP_SCRIPT`   | `/usr/local/bin/backup-sapp.sh`                         |
| `LOCAL_BACKUP_DIR`| `/var/backups/sapp`                                     |
| `R2_DB_PATH`      | `weirdoware-r2:weirdoware-backups/sapp/database`        |
| `R2_STORAGE_PATH` | `weirdoware-r2:weirdoware-backups/sapp/storage`         |
| `QUEUE_SERVICE`   | `sapp-queue.service`                                    |
| `ASSUME_YES`      | `0` (`1` salta las confirmaciones — solo sin migraciones)|

## 4. Comandos PROHIBIDOS en producción

Nunca de forma rutinaria (pueden destruir datos; ni el rollback los recupera):

```
php artisan migrate:fresh
php artisan migrate:refresh
php artisan migrate:reset
php artisan db:wipe
```

## 5. Si algo va mal

- **502 Bad Gateway:** revisar servicios locales antes de tocar DNS/túnel
  (instructivo §13). Probar el origen: `curl -I -H "Host: sapp.weirdoware.com" http://127.0.0.1:8080`.
- **Migración fallida:** no improvisar; `migrate:status`, logs, y si procede
  `migrate:rollback --step=1`; ante pérdida de datos, restaurar desde R2 (§8–§9).
- **Servicios críticos:** `sudo systemctl is-active nginx mysql php8.4-fpm cloudflared sapp-queue.service cron`.

## 6. Nota sobre rclone (no romper CloudPanel)

El servidor tiene dos `rclone`: `/usr/bin/rclone` (APT, del que depende CloudPanel)
y `/usr/local/bin/rclone` (versión moderna para los backups). **PROHIBIDO**
`apt remove rclone` / `apt purge rclone` (incidente del 4-oct-2026: tumbó Nginx y
MySQL → 502). Ver instructivo §12 y §15.
