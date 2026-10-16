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

## Rotar APP_KEY (NECESARIO — confirmado comprometida)

El `.env` real nunca se versionó, PERO las plantillas `.env.dev` y `.env.local` SÍ
contenían una `APP_KEY` real publicada en GitHub, y se confirmó que **es la misma
que usa producción**. Por tanto la clave de cifrado de la app está comprometida y
HAY QUE ROTARLA.

Verificación hecha: la app NO usa cifrado de campos en la base de datos (no hay
casts `encrypted` ni `Crypt::` en el código), y `SESSION_ENCRYPT=false`. Rotar la
`APP_KEY` por tanto NO corrompe datos persistidos: su único efecto es invalidar
sesiones y cookies "remember me", que es justo lo deseable tras la exposición.

```bash
sudo -iu weirdoware-sapp
cd ~/htdocs/sapp.weirdoware.com
php artisan down
php artisan key:generate --force    # genera y escribe una APP_KEY nueva en .env
php artisan config:cache
php artisan up
```

> ⚠️ Solo rotar `APP_KEY` sin más es seguro en ESTE proyecto porque no hay datos
> cifrados en BD. Si en el futuro añades columnas cifradas, rotar la clave exigiría
> re-cifrar esos datos antes del cambio.

> Tras rotar, limpia también la clave vieja del historial de git si no lo has hecho
> (ver `scripts/LIMPIAR-HISTORIAL.md`, con `git filter-repo --replace-text`).

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
