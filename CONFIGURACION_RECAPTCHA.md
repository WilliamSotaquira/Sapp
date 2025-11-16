# 🔒 Configuración de Google reCAPTCHA v2

## 📋 Descripción

Se ha implementado Google reCAPTCHA v2 en el formulario de consulta pública (`/consultar`) para prevenir abuso y spam mediante bots automatizados.

---

## 🎯 ¿Qué es reCAPTCHA?

Google reCAPTCHA es un servicio gratuito que protege tu sitio web contra spam y abuso. Utiliza análisis de riesgo avanzado para distinguir entre humanos y bots.

**Versión Implementada**: reCAPTCHA v2 (Checkbox "No soy un robot")

---

## 🔑 Obtener Claves de Google reCAPTCHA

### Paso 1: Acceder a la Consola de Google reCAPTCHA

1. Visita: https://www.google.com/recaptcha/admin/create
2. Inicia sesión con tu cuenta de Google

### Paso 2: Registrar un Nuevo Sitio

Completa el formulario con la siguiente información:

**Label (Etiqueta)**:
```
Sapp - Sistema de Gestión de Servicios
```

**reCAPTCHA type (Tipo)**:
- ✅ Selecciona: **reCAPTCHA v2**
- ✅ Marca: **"I'm not a robot" Checkbox**

**Domains (Dominios)**:
```
sapp.weirdoware.com
localhost
127.0.0.1
```

**Owners (Propietarios)**:
- Añade tu email de Google

**Accept the reCAPTCHA Terms of Service**:
- ✅ Marca la casilla de aceptación

### Paso 3: Obtener las Claves

Después de registrar el sitio, Google te proporcionará:

1. **Site Key (Clave del Sitio)**: Visible públicamente en el HTML
2. **Secret Key (Clave Secreta)**: Debe mantenerse privada en el servidor

---

## ⚙️ Configuración en el Proyecto

### 1. Agregar Variables de Entorno

Edita el archivo `.env` y agrega las siguientes líneas:

```env
# Google reCAPTCHA v2
RECAPTCHA_SITE_KEY=tu_site_key_aqui
RECAPTCHA_SECRET_KEY=tu_secret_key_aqui
```

**Ejemplo**:
```env
RECAPTCHA_SITE_KEY=6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
RECAPTCHA_SECRET_KEY=6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe
```

> ⚠️ **Nota**: Las claves del ejemplo anterior son claves de prueba de Google que siempre retornan éxito. Debes reemplazarlas con tus propias claves para producción.

### 2. Verificar Configuración

El archivo `config/services.php` ya contiene la configuración:

```php
'recaptcha' => [
    'site_key' => env('RECAPTCHA_SITE_KEY'),
    'secret_key' => env('RECAPTCHA_SECRET_KEY'),
],
```

### 3. Limpiar Cache de Configuración

Después de agregar las claves al `.env`, ejecuta:

```bash
php artisan config:clear
php artisan config:cache
```

---

## 🧪 Claves de Prueba de Google

Para desarrollo y testing, Google proporciona claves especiales que **siempre pasan la validación**:

```env
# Claves de PRUEBA (solo para desarrollo/testing)
RECAPTCHA_SITE_KEY=6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
RECAPTCHA_SECRET_KEY=6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe
```

> ⚠️ **Importante**: Estas claves solo deben usarse en desarrollo. Para producción, usa claves reales de tu cuenta.

---

## 📍 Ubicación del CAPTCHA

El reCAPTCHA se muestra en:

**Ruta**: `/consultar`  
**Vista**: `resources/views/public/tracking/index.blade.php`  
**Posición**: Entre el campo de búsqueda y el botón "Buscar Mi Solicitud"

---

## 🔍 Validación del Backend

El controlador `PublicTrackingController` realiza la validación:

```php
// app/Http/Controllers/PublicTrackingController.php

public function search(Request $request)
{
    // 1. Validar campos del formulario
    $validated = $request->validate([
        'query' => 'required|string|min:3',
        'type' => 'required|in:ticket,email',
        'g-recaptcha-response' => 'required',
    ], [
        'g-recaptcha-response.required' => 'Por favor completa la verificación de seguridad (reCAPTCHA).',
    ]);

    // 2. Verificar reCAPTCHA con Google
    $recaptchaResponse = $request->input('g-recaptcha-response');
    $recaptchaSecret = config('services.recaptcha.secret_key');
    
    $verifyResponse = file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}"
    );
    $responseData = json_decode($verifyResponse);

    // 3. Si falla la verificación, retornar error
    if (!$responseData->success) {
        return back()
            ->withInput()
            ->withErrors(['g-recaptcha-response' => 'La verificación de seguridad falló. Por favor intenta nuevamente.']);
    }

    // 4. Continuar con la búsqueda si pasa la verificación
    // ...
}
```

---

## 🎨 Apariencia del CAPTCHA

El reCAPTCHA se mostrará como una caja con checkbox:

```
┌─────────────────────────────────────┐
│  ☐  No soy un robot                 │
│                          [reCAPTCHA] │
└─────────────────────────────────────┘
```

Al hacer clic, puede mostrar desafíos adicionales como:
- Seleccionar imágenes que contengan semáforos
- Seleccionar imágenes con puentes
- Seleccionar imágenes con autobuses

---

## 🚀 Despliegue en Producción

### Checklist Pre-Deploy

- [ ] Obtener claves reales de Google reCAPTCHA
- [ ] Registrar dominio `sapp.weirdoware.com` en reCAPTCHA
- [ ] Agregar claves al `.env` de producción
- [ ] Ejecutar `php artisan config:clear` en servidor
- [ ] Probar formulario público en producción
- [ ] Verificar que bloquea envíos sin CAPTCHA

### Comando de Deploy

```bash
# En el servidor de producción
cd /ruta/al/proyecto
git pull origin main
php artisan config:clear
php artisan config:cache
php artisan route:cache
```

---

## 🔐 Seguridad

### ✅ Lo que ESTÁ Protegido

- ✅ Formulario de búsqueda pública (`/consultar`)
- ✅ Prevención de spam masivo
- ✅ Protección contra bots automatizados
- ✅ Rate limiting adicional

### ⚠️ Consideraciones

- El reCAPTCHA solo protege el formulario de búsqueda
- Las vistas de detalle siguen siendo accesibles con URL directa
- Si se requiere más seguridad, considerar:
  - Verificación de email antes de mostrar detalles
  - Rate limiting más agresivo
  - Logging de IPs sospechosas

---

## 📊 Monitoreo

### Ver Estadísticas de reCAPTCHA

1. Accede a: https://www.google.com/recaptcha/admin
2. Selecciona tu sitio "Sapp - Sistema de Gestión de Servicios"
3. Verás métricas como:
   - Total de solicitudes
   - Solicitudes bloqueadas
   - Score promedio (si usaras v3)
   - Distribución geográfica

---

## 🐛 Troubleshooting

### Error: "Please complete the security verification"

**Causa**: El usuario no marcó el checkbox  
**Solución**: El usuario debe hacer clic en "No soy un robot"

### Error: "La verificación de seguridad falló"

**Causas posibles**:
1. Claves incorrectas en `.env`
2. Dominio no registrado en Google reCAPTCHA
3. Firewall bloqueando conexión a Google
4. Token expirado (usuario tardó mucho en enviar)

**Soluciones**:
```bash
# 1. Verificar claves
php artisan tinker
>>> config('services.recaptcha.site_key')
>>> config('services.recaptcha.secret_key')

# 2. Limpiar cache
php artisan config:clear

# 3. Verificar dominio en Google reCAPTCHA Admin
```

### Error: "ERROR for site owner: Invalid domain"

**Causa**: El dominio actual no está registrado en reCAPTCHA  
**Solución**: Agregar el dominio en https://www.google.com/recaptcha/admin

### reCAPTCHA no se muestra

**Causas posibles**:
1. Script de Google bloqueado (AdBlockers)
2. Site Key incorrecta
3. Error de JavaScript

**Solución**:
```bash
# Verificar que la variable esté definida
php artisan tinker
>>> config('services.recaptcha.site_key')

# Verificar en el navegador (F12 Console)
# Buscar errores de JavaScript relacionados con reCAPTCHA
```

---

## 📚 Recursos Adicionales

- **Documentación Oficial**: https://developers.google.com/recaptcha/docs/display
- **Admin Console**: https://www.google.com/recaptcha/admin
- **Guía de Implementación**: https://developers.google.com/recaptcha/docs/v2
- **FAQ**: https://developers.google.com/recaptcha/docs/faq

---

## 🔄 Migración a reCAPTCHA v3 (Futuro)

Si en el futuro se desea migrar a reCAPTCHA v3 (sin checkbox, análisis invisible):

### Ventajas de v3:
- ✅ Sin interacción del usuario
- ✅ Score de 0.0 a 1.0 de probabilidad de ser bot
- ✅ Mejor UX (invisible)

### Desventajas de v3:
- ❌ Menos preciso en algunos casos
- ❌ Requiere definir threshold de score
- ❌ Más complejo de configurar

---

**Última actualización**: 16 de noviembre de 2025  
**Versión**: 1.0  
**Estado**: ✅ Implementado y listo para producción  
**Responsable**: Equipo de Desarrollo Sapp
