# Despliegue de Sapp

Despliegue en producción (servidor dedicado, CloudPanel). Un solo script idempotente
reemplaza los antiguos `deploy-status-fix.*` y `PRODUCCION_COMANDOS.md`.

## 1. Configurar el acceso SSH (una sola vez, en tu máquina)

Añade este bloque a tu `~/.ssh/config` (en Windows: `C:\Users\willi\.ssh\config`):

```sshconfig
Host sapp-prod
    HostName 100.84.78.120
    User william-sora
    IdentityFile ~/.ssh/id_ed25519   # ajusta al nombre de tu clave
    IdentitiesOnly yes
```

A partir de ahí te conectas con:

```bash
ssh sapp-prod
```

> El despliegue corre como el usuario del sitio. Tras conectarte:
> `sudo -iu weirdoware-sapp` y luego `cd ~/htdocs/sapp.weirdoware.com`.

## 2. Desplegar

### Opción A — en el servidor (recomendada)

```bash
ssh sapp-prod
sudo -iu weirdoware-sapp
cd ~/htdocs/sapp.weirdoware.com
bash scripts/deploy.sh
```

El script hace, en orden: backup de BD → modo mantenimiento → `git pull` →
`composer install --no-dev` → `npm ci && npm run build` → migraciones →
recache de config/route/view → reinicio de cola → salir de mantenimiento.

Si algo falla, aborta y te imprime los comandos exactos de rollback (reset al
commit anterior y restauración de BD desde el backup recién creado).

### Opción B — evitar escribir la contraseña de la BD cada vez

Crea `~/.my.cnf` en el servidor (como usuario `weirdoware-sapp`) con permisos 600:

```ini
[client]
user=weirdoware_sapp
password=TU_PASSWORD
```

Así `mysqldump` no pedirá contraseña y el deploy corre sin interacción.

## 3. Variables ajustables

El script lee estas variables de entorno (con valores por defecto para este
servidor). Solo necesitas pasarlas si cambia algo:

| Variable       | Por defecto                        |
|----------------|------------------------------------|
| `PROJECT_DIR`  | `~/htdocs/sapp.weirdoware.com`     |
| `DB_USER`      | `weirdoware_sapp`                  |
| `DB_NAME`      | `weirdoware_sapp`                  |
| `BACKUP_DIR`   | `~/backups`                        |
| `GIT_BRANCH`   | `main`                             |
| `BUILD_ASSETS` | `1` (pon `0` para saltar el build) |
| `PHP_FPM_SERVICE` | `php8.4-fpm` (servicio php-fpm del sitio) |

Ejemplo saltando el build de assets:

```bash
BUILD_ASSETS=0 bash scripts/deploy.sh
```
