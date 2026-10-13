# Eliminar archivos sensibles del historial de git

El commit `f45b9e8` (y otros anteriores) dejaron en el **historial** dumps SQL con
hashes de contraseñas y tokens, además de tarballs y backups. Borrarlos en un commit
nuevo NO los quita del historial: hay que reescribirlo y forzar el push.

> ⚠️ Esto reescribe la historia y requiere `git push --force`. Es irreversible en el
> remoto. Como trabajas solo en el repo, el riesgo es bajo, pero haz primero un backup
> del repo completo (copia la carpeta entera, o `git clone` a otro sitio).

## Requisito: git-filter-repo

`git filter-repo` es la herramienta recomendada (más segura y rápida que `filter-branch`).

```powershell
# Opción A: con pip (necesita Python)
pip install git-filter-repo

# Opción B: descargar el único archivo .py y ponerlo en el PATH
#   https://github.com/newren/git-filter-repo
```

Comprueba que está disponible:

```powershell
git filter-repo --version
```

## Paso 1 — Backup del repo (por si acaso)

```powershell
cd C:\Users\willi
Copy-Item -Recurse Sapp Sapp-backup-pre-rewrite
```

## Paso 2 — Reescribir el historial

Desde la raíz del repo (`C:\Users\willi\Sapp`):

```powershell
git filter-repo --invert-paths `
  --path database/dumps/sapp_datos_PROD.sql `
  --path sdm_portal_backup.sql `
  --path backups/sapp_backup_20260217_111703.sql `
  --path backups/sapp_backup_20260217_171810.sql `
  --path laravel-app.tar.gz `
  --path sdm-production.tar.gz `
  --path-glob '*.sql' `
  --path-glob '*.tar.gz'
```

Esto elimina esos archivos de **todos** los commits donde aparezcan.

> `git filter-repo` elimina el remoto `origin` por seguridad. Hay que volver a añadirlo.

## Paso 3 — Volver a enlazar el remoto y forzar el push

```powershell
git remote add origin <URL_DEL_REMOTO>   # la misma de antes
git push origin --force --all
git push origin --force --tags
```

Para ver la URL del remoto que tenías (antes de ejecutar filter-repo):

```powershell
git remote get-url origin
```

## Paso 4 — Verificar que el dump ya no está en el historial

```powershell
git log --all --oneline -- database/dumps/sapp_datos_PROD.sql
# No debe devolver nada
```

## Paso 5 — Rotar credenciales (ver sección en el chat)

Una vez limpio el historial, rota las credenciales que estuvieron expuestas.
