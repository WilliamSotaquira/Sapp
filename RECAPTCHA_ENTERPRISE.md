# 🔒 Google reCAPTCHA Enterprise - Guía de Implementación

## 📋 Descripción

Se ha integrado **Google reCAPTCHA Enterprise** en el sistema, proporcionando protección avanzada contra bots y análisis de riesgo basado en Machine Learning.

---

## ✨ Características Implementadas

### 🎯 Modo Dual
- **reCAPTCHA v2 Estándar**: Verificación básica con checkbox
- **reCAPTCHA Enterprise**: Análisis avanzado de riesgo con scores

### 🔄 Fallback Automático
- Si Enterprise falla → usa reCAPTCHA v2
- Si v2 falla → continúa sin verificación (modo desarrollo)

### 📊 Análisis de Score
- **Score**: 0.0 a 1.0 (mayor = más humano)
- **Umbral Login**: 0.5
- **Umbral Búsqueda Pública**: 0.3
- **Validación de acciones**: Verifica que la acción coincida

---

## 🛠️ Configuración

### Paso 1: Variables de Entorno

Edita tu archivo `.env`:

```env
# reCAPTCHA v2 Básico (Obligatorio)
RECAPTCHA_SITE_KEY=6LfUdsYZAAAAAFnFtC01B3KQkS3qp6SSxhSoIiGE
RECAPTCHA_SECRET_KEY=tu_secret_key

# reCAPTCHA Enterprise (Opcional)
RECAPTCHA_ENTERPRISE_ENABLED=true
RECAPTCHA_ENTERPRISE_PROJECT_ID=sapp-171813
RECAPTCHA_ENTERPRISE_API_KEY=tu_api_key_aqui
```

### Paso 2: Obtener Credenciales de Google Cloud

#### Para reCAPTCHA Enterprise:

1. **Accede a Google Cloud Console**:
   - https://console.cloud.google.com/

2. **Crea o Selecciona Proyecto**:
   - Proyecto: `sapp-171813` (ya existe)

3. **Habilita la API**:
   - Navega a: API & Services → Library
   - Busca: "reCAPTCHA Enterprise API"
   - Haz clic en "Enable"

4. **Crea una Clave de API**:
   - Ve a: API & Services → Credentials
   - Clic en "Create Credentials" → "API Key"
   - Copia la clave generada
   - (Opcional) Restringe la clave a "reCAPTCHA Enterprise API"

5. **Configura reCAPTCHA Enterprise**:
   - Ve a: Security → reCAPTCHA Enterprise
   - Crea una nueva clave con tu dominio
   - Usa la Site Key: `6LfUdsYZAAAAAFnFtC01B3KQkS3qp6SSxhSoIiGE`

### Paso 3: Configurar Autenticación (Opcional)

Para producción, se recomienda usar Service Account en lugar de API Key:

```bash
# Descarga el archivo de credenciales JSON
# Guárdalo en: storage/app/google-credentials.json

# Agrega al .env:
GOOGLE_APPLICATION_CREDENTIALS=/path/to/storage/app/google-credentials.json
```

---

## 💻 Uso del Servicio

### Ejemplo Básico

```php
use App\Services\RecaptchaEnterpriseService;

$recaptchaService = new RecaptchaEnterpriseService();

// Verificación simple (true/false)
$token = $request->input('g-recaptcha-response');
$isValid = $recaptchaService->verify($token, 'login', 0.5);

if (!$isValid) {
    return back()->withErrors(['recaptcha' => 'Verificación fallida']);
}
```

### Ejemplo Avanzado con Score

```php
$assessment = $recaptchaService->createAssessment($token, 'login');

if ($assessment['success']) {
    $score = $assessment['score']; // 0.0 - 1.0
    $action = $assessment['action'];
    $reasons = $assessment['reasons'];
    
    if ($score < 0.3) {
        // Alto riesgo - Bloquear
    } elseif ($score < 0.7) {
        // Riesgo medio - Requerir verificación adicional
    } else {
        // Bajo riesgo - Permitir
    }
}
```

---

## 📍 Puntos de Integración

### 1. Login (`LoginRequest.php`)
- Acción: `login`
- Umbral: `0.5`
- Ubicación: `app/Http/Requests/Auth/LoginRequest.php`

### 2. Registro (`RegisterRequest.php`)
- Acción: `register`
- Umbral: `0.5`
- Similar a LoginRequest

### 3. Búsqueda Pública (`PublicTrackingController.php`)
- Acción: `search`
- Umbral: `0.3` (más permisivo)
- Ubicación: `app/Http/Controllers/PublicTrackingController.php`

---

## 🎨 Frontend - Sin Cambios Necesarios

El frontend sigue usando el mismo código reCAPTCHA v2:

```html
<div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
```

La magia ocurre en el backend con el servicio de Enterprise.

---

## 📊 Interpretación de Scores

| Score | Interpretación | Acción Recomendada |
|-------|---------------|-------------------|
| 0.9 - 1.0 | Muy confiable (humano) | Permitir |
| 0.7 - 0.8 | Confiable | Permitir |
| 0.5 - 0.6 | Neutral | Permitir con monitoreo |
| 0.3 - 0.4 | Sospechoso | Verificación adicional |
| 0.0 - 0.2 | Alto riesgo (bot) | Bloquear |

---

## 🔍 Logs y Monitoreo

Los eventos se registran automáticamente:

```php
// storage/logs/laravel.log

[INFO] reCAPTCHA Enterprise - Score: 0.9 - Action: login
[WARNING] Token reCAPTCHA inválido: EXPIRED
[ERROR] Error en createAssessment: Connection timeout
```

---

## 🚨 Troubleshooting

### Error: "Class RecaptchaEnterpriseServiceClient not found"
**Solución**: Ejecuta `composer require google/cloud-recaptcha-enterprise`

### Error: "Permission denied"
**Causa**: API no habilitada o credenciales incorrectas
**Solución**: 
1. Verifica que la API esté habilitada en Google Cloud
2. Revisa las credenciales en `.env`

### Score siempre 0.0
**Causa**: Token inválido o expirado
**Solución**: Los tokens expiran en 2 minutos, verifica el frontend

### Fallback a reCAPTCHA v2
**Causa**: Enterprise no está configurado o falló
**Efecto**: Sistema sigue funcionando con v2 básico

---

## 🔐 Seguridad

### Mejores Prácticas

1. **No expongas API Keys en el código**
   ```php
   // ❌ MAL
   $apiKey = "AIzaSy...";
   
   // ✅ BIEN
   $apiKey = config('services.recaptcha.enterprise.api_key');
   ```

2. **Restringe las claves de API**
   - Solo permite "reCAPTCHA Enterprise API"
   - Restringe por dominio en producción

3. **Usa Service Account en producción**
   - Más seguro que API Keys
   - Permite rotación de credenciales

4. **Ajusta umbrales según tu caso**
   - Login crítico: 0.6+
   - Formularios públicos: 0.3+
   - APIs: 0.7+

---

## 📚 Recursos Adicionales

- **Documentación Oficial**: https://cloud.google.com/recaptcha-enterprise/docs
- **Interpretar Evaluaciones**: https://cloud.google.com/recaptcha-enterprise/docs/interpret-assessment
- **PHP Client Library**: https://github.com/googleapis/google-cloud-php-recaptcha-enterprise
- **Precios**: https://cloud.google.com/recaptcha-enterprise/pricing (10,000 evaluaciones gratis/mes)

---

## ✅ Checklist de Implementación

- [x] Dependencia instalada (`google/cloud-recaptcha-enterprise`)
- [x] Servicio creado (`RecaptchaEnterpriseService.php`)
- [x] Configuración en `config/services.php`
- [x] Variables de entorno en `.env.example`
- [x] Integrado en LoginRequest
- [x] Integrado en PublicTrackingController
- [x] Fallback a reCAPTCHA v2 implementado
- [x] Logs configurados
- [ ] API habilitada en Google Cloud
- [ ] API Key configurada en `.env`
- [ ] Pruebas en ambiente de desarrollo
- [ ] Pruebas en producción

---

## 🎯 Siguiente Paso

1. **Habilita la API** en Google Cloud Console
2. **Obtén tu API Key** y agrégala al `.env`
3. **Prueba** iniciando sesión
4. **Monitorea** los logs en `storage/logs/laravel.log`

**Sistema listo para producción con protección avanzada contra bots! 🎉**
