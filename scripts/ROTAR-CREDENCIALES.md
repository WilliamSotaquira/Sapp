# Rotar credenciales tras la exposición del dump

El dump `sapp_datos_PROD.sql` estuvo publicado en GitHub con hashes bcrypt de
contraseñas y `remember_token`. Aunque se reescribió el historial, hay que tratar
esas credenciales como comprometidas. Ejecuta esto en **producción**.

## Paso 0 — Conectarse al servidor

```bash
ssh william-sora@100.84.78.120
sudo -iu weirdoware-sapp
cd ~/htdocs/sapp.weirdoware.com
```

## Paso 1 — Invalidar todos los remember tokens (lo más importante)

Corta cualquier sesión "recuérdame" que se pudiera haber robado del dump.

Opción A — con Artisan (recomendada, respeta Eloquent):

```bash
php artisan tinker --execute="\App\Models\User::query()->update(['remember_token' => null]);"
```

Opción B — SQL directo:

```bash
mysql -u weirdoware_sapp -p weirdoware_sapp -e "UPDATE users SET remember_token = NULL;"
```

## Paso 2 — Resetear la contraseña del admin

Cuenta más valiosa: `william.sotaquira@gmail.com`. Pon una contraseña nueva y fuerte.

```bash
php artisan tinker
```

Dentro de tinker:

```php
$u = \App\Models\User::where('email', 'william.sotaquira@gmail.com')->first();
$u->password = \Illuminate\Support\Facades\Hash::make('NUEVA_PASSWORD_FUERTE_AQUI');
$u->remember_token = null;
$u->save();
exit
```

## Paso 3 — (Opcional pero recomendado) forzar re-login de todos

Invalida todas las sesiones activas. Si usas `SESSION_DRIVER=database`:

```bash
php artisan tinker --execute="\Illuminate\Support\Facades\DB::table('sessions')->truncate();"
```

> Si el driver de sesión es `file`, borra el contenido de `storage/framework/sessions/`.

## Paso 4 — (Opcional, solo si el repo fue PÚBLICO) resetear a todos

Marca a todos para que deban cambiar la contraseña, o fuerza un reset masivo según
tu flujo. Como mínimo, avisa a los usuarios de que cambien su contraseña.

## Paso 5 — Verificar

```bash
# No debe quedar ningún remember_token
mysql -u weirdoware_sapp -p weirdoware_sapp -e "SELECT COUNT(*) AS con_token FROM users WHERE remember_token IS NOT NULL;"
# Debe devolver 0
```

## Sobre APP_KEY

El `.env` NO estaba en el dump (solo datos de tablas), así que la `APP_KEY` no se
filtró por esta vía. Confirma que `.env` nunca se versionó:

```bash
# En tu máquina, dentro del repo:
git log --all --oneline -- .env
# Si no devuelve nada, APP_KEY está a salvo y NO hace falta rotarla.
```

> ⚠️ Rotar `APP_KEY` invalidaría datos cifrados existentes (cookies, valores
> encriptados). Hazlo solo si confirmas que se filtró.

---

# Purgar el caché de commits en GitHub

Reescribir el historial y hacer force-push NO borra inmediatamente los objetos
viejos en GitHub: siguen accesibles por su hash un tiempo hasta la recolección de
basura. Para forzar su eliminación:

1. Abre un ticket en GitHub Support: https://support.github.com/contact
   Pide explícitamente: *"garbage collect / purge cached views of commits removed
   from the repository WilliamSotaquira/Sapp after a history rewrite"*.
2. Indica que removiste datos sensibles y que necesitas que purguen los objetos
   inalcanzables y las vistas cacheadas de los commits antiguos.
3. Si hay forks del repo, los commits pueden sobrevivir ahí: revisa y pide su
   eliminación si aplica.

Referencia oficial: GitHub — "Removing sensitive data from a repository".
