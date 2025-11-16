# 📋 Sistema de Consulta Pública de Solicitudes v2.0

## 🎯 Descripción General

Sistema completo que permite a los usuarios regulares consultar el estado de sus solicitudes de servicio **sin necesidad de iniciar sesión** en el sistema. Diseñado para proporcionar transparencia total y acceso fácil a la información de seguimiento.

---

## ✨ Características Principales

### 1. **Búsqueda Dual Mejorada**
- ✅ **Por Número de Ticket**: Consulta directa de una solicitud específica
- ✅ **Por Correo Electrónico**: Ver todas las solicitudes asociadas a un email
- ✅ Ejemplos visuales de formato esperado
- ✅ Validación en tiempo real

### 2. **Información Detallada Completa**
- Estado actual con codificación de colores
- Historial completo de cambios de estado (nuevo en v2.0)
- Información del servicio solicitado
- Nivel de criticidad visual
- Fechas importantes (creación, aceptación, resolución, cierre)
- Usuario técnico asignado con información de contacto
- Comentarios y observaciones de cada cambio

### 3. **Historial de Seguimiento (NUEVO)**
- Timeline visual interactivo con íconos y colores
- Registro completo de quién hizo cada cambio
- Fecha y hora precisa de cada actualización
- Comentarios asociados a cada cambio de estado
- Estado anterior y nuevo para cada transición
- Metadata adicional (IP, navegador, ruta)

### 4. **Funciones de Compartir**
- 🟢 **WhatsApp**: Mensaje pre-formateado con emojis
- 📧 **Email**: Correo con información completa
- 📋 **Copiar Enlace**: URL directa al portapapeles con notificación

---

## 🌐 Rutas Públicas

```php
// Formulario de búsqueda
GET /consultar

// Procesar búsqueda
POST /consultar/search

// Ver detalle de solicitud específica
GET /consultar/{ticketNumber}

// Listar múltiples solicitudes (búsqueda por email)
GET /consultar/list
```

**Sin middleware de autenticación** - Acceso completamente público

---

## 📊 Estados y Colores

| Estado | Color | Ícono | Descripción |
|--------|-------|-------|-------------|
| `NUEVA` | Azul 🔵 | ⭐ fa-star | Solicitud recién creada |
| `EN_REVISION` | Amarillo 🟡 | 🔍 fa-search | En proceso de revisión |
| `ACEPTADA` | Verde 🟢 | ✅ fa-check | Aceptada por técnico |
| `EN_PROGRESO` | Púrpura 🟣 | ⚙️ fa-cog | En proceso de resolución |
| `RESUELTA` | Teal 🔷 | ✓ fa-check-circle | Problema resuelto |
| `CERRADA` | Gris ⚪ | 🔒 fa-lock | Caso cerrado |
| `RECHAZADA` | Rojo 🔴 | ❌ fa-times-circle | Solicitud rechazada |
| `PAUSADA` | Naranja 🟠 | ⏸ fa-pause-circle | Temporalmente pausada |

---

## 🗂️ Estructura de Archivos

### Controlador
```
app/Http/Controllers/PublicTrackingController.php
```

**Métodos Principales:**
```php
index()                  // Formulario de búsqueda
search(Request $request) // Procesar búsqueda (ticket/email)
show($ticketNumber)      // Detalle con historial completo
```

### Modelos
```
app/Models/
├── ServiceRequest.php
└── ServiceRequestStatusHistory.php (NUEVO)
```

### Observer
```
app/Observers/ServiceRequestObserver.php (NUEVO)
```
Registra automáticamente cada cambio de estado

### Vistas
```
resources/views/public/tracking/
├── index.blade.php      # Formulario de búsqueda mejorado
├── show.blade.php       # Detalle con historial completo
└── list.blade.php       # Listado mejorado de múltiples solicitudes
```

---

## 💾 Base de Datos

### Tabla: `service_request_status_histories` (NUEVA)

```sql
CREATE TABLE service_request_status_histories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    service_request_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(50) NOT NULL,
    previous_status VARCHAR(50) NULL,
    comments TEXT NULL,
    changed_by BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    -- Foreign Keys
    CONSTRAINT service_request_status_histories_service_request_id_foreign 
        FOREIGN KEY (service_request_id) 
        REFERENCES service_requests(id) 
        ON DELETE CASCADE,
    
    CONSTRAINT service_request_status_histories_changed_by_foreign 
        FOREIGN KEY (changed_by) 
        REFERENCES users(id) 
        ON DELETE SET NULL,
    
    -- Índices Optimizados
    INDEX sr_status_hist_req_created_idx (service_request_id, created_at),
    INDEX sr_status_hist_status_idx (status)
);
```

### Modelo: `ServiceRequestStatusHistory`

**Campos Fillable:**
```php
[
    'service_request_id',
    'status',
    'previous_status',
    'comments',
    'changed_by',
    'ip_address',
    'user_agent',
    'metadata'
]
```

**Relaciones:**
```php
serviceRequest() // belongsTo ServiceRequest
changedBy()      // belongsTo User
```

**Accessors:**
```php
status_label  // Nombre legible del estado
status_color  // Color para UI (blue, green, red, etc.)
status_icon   // Ícono FontAwesome
```

**Scopes:**
```php
ordered()           // ->orderBy('created_at', 'desc')
forRequest($id)     // ->where('service_request_id', $id)
```

---

## 🔄 Observer Automático

### `ServiceRequestObserver`

**Eventos Capturados:**

#### 1. `created()` - Al crear solicitud
```php
- Registra estado inicial automáticamente
- Comentario: "Solicitud creada"
- Usuario: ID del solicitante
- IP: Dirección IP del creador
```

#### 2. `updating()` - Al actualizar solicitud
```php
- Detecta cambios en campo 'status' con isDirty()
- Si hay cambio de estado:
  * Registra estado anterior y nuevo
  * Captura usuario autenticado
  * Guarda IP, user agent
  * Almacena metadata (ruta, método HTTP)
```

**Ejemplo de Metadata:**
```json
{
    "route": "service-requests.update",
    "method": "PUT",
    "migration": false
}
```

---

## 🎨 Interfaz de Usuario Mejorada

### Vista de Búsqueda (`index.blade.php`)

**Mejoras v2.0:**
- ✅ Ícono grande circular en header
- ✅ Subtítulo "Sin necesidad de iniciar sesión"
- ✅ Cards de opción mejoradas con:
  - Bordes más gruesos
  - Hover effects con sombra
  - Ejemplos de formato en cada opción
  - Descripciones más claras
- ✅ Input con validación visual
- ✅ Botón con gradiente y animación
- ✅ Sección "¿Cómo funciona?" con pasos numerados
- ✅ Box de consejo destacado (amarillo)

### Vista de Detalle (`show.blade.php`)

**Secciones:**

1. **Header con Estado Actual**
   - Ticket number grande
   - Badge de estado con color e ícono

2. **Información Básica**
   - Título descriptivo
   - Servicio solicitado
   - Nivel de criticidad con ícono
   - Descripción completa

3. **Timeline de Fechas Importantes**
   - Creación
   - Aceptación
   - Respuesta
   - Resolución
   - Cierre

4. **Técnico Asignado**
   - Nombre y email
   - Ícono de usuario

5. **Historial de Seguimiento** (NUEVO)
   - Cards por cada cambio de estado
   - Círculo con color del estado
   - Ícono representativo
   - Fecha y hora formateada
   - Estado anterior (si aplica)
   - Comentarios en caja destacada
   - Nombre del usuario que hizo el cambio
   - Hover effect

6. **Fallback sin Historial**
   - Muestra estado actual simple
   - Fechas clave disponibles

### Vista de Listado (`list.blade.php`)

**Mejoras v2.0:**

- ✅ Header mejorado con:
  - Botón "Nueva búsqueda" estilizado
  - Círculo con ícono
  - Contador de resultados
  - Info de paginación

- ✅ Cards optimizadas:
  - Border izquierdo colorido según estado
  - Ícono de ticket en círculo gradiente
  - Enlace externo visible
  - Badge de estado con borde
  - Grid de información en boxes circulares
  - Última actualización con diffForHumans()
  - Botón con gradiente y animación

- ✅ Mensaje "No hay resultados"
  - Ícono grande
  - Texto explicativo
  - Botón para nueva búsqueda

- ✅ Paginación estilizada en card

---

## 📱 Responsive Design

**Breakpoints Tailwind:**
```
sm:  640px  (Mobile landscape / Tablet portrait)
md:  768px  (Tablet)
lg:  1024px (Desktop)
xl:  1280px (Large desktop)
```

**Adaptaciones Implementadas:**

- Formularios: 1 columna en mobile → 2 en tablet+
- Grid info: 1→2→3 columnas según tamaño
- Botones: full-width en mobile
- Timeline: vertical optimizada
- Cards: apilables con padding ajustable
- Texto: tamaños responsivos (text-sm / sm:text-base)

---

## 🔗 Funcionalidad de Compartir

### WhatsApp Share
```javascript
URL: https://wa.me/?text={mensaje}

Mensaje formato:
🎫 *Solicitud de Servicio*

📋 *Ticket:* {ticketNumber}
📊 *Estado:* {status}
🔧 *Servicio:* {serviceName}
📅 *Fecha:* {createdDate}

🔗 Consulta el estado completo aquí:
{publicUrl}
```

### Email Share
```html
mailto:?subject=Consulta%20de%20Solicitud%20{ticket}
&body=Te%20comparto%20el%20enlace%20para%20consultar%20el%20estado...
```

### Copy Link
```javascript
// Usa Clipboard API moderna
navigator.clipboard.writeText(url)
    .then(() => showNotification())
    .catch(() => fallbackCopy());

// Fallback con textarea temporal para navegadores antiguos
function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
}
```

---

## 🚀 Instalación y Configuración

### 1. Ejecutar Migraciones

```bash
php artisan migrate

# Migraciones incluidas:
# - 2025_11_16_172802_create_service_request_status_histories_table.php
# - 2025_11_16_180000_populate_initial_status_history.php
```

### 2. Observer (Ya Registrado)

En `app/Providers/AppServiceProvider.php`:
```php
public function boot(): void
{
    ServiceRequest::observe(ServiceRequestObserver::class);
}
```

### 3. Poblar Historial Inicial

```bash
# Ya ejecutado automáticamente en migración
# Crea registro inicial para todas las solicitudes existentes
# Valida existencia de usuarios antes de asignar
```

### 4. Verificar Instalación

```bash
php artisan tinker

# Verificar registros
echo ServiceRequest::count();          // Total de solicitudes
echo ServiceRequestStatusHistory::count(); // Total de historiales

# Ver ejemplo
ServiceRequestStatusHistory::with('changedBy')->first();
```

---

## 📈 Estadísticas Actuales

- ✅ **Total de solicitudes**: 49
- ✅ **Total de historiales**: 49
- ✅ **Ejemplo de ticket**: `INF-PU-M-251112-001`
- ✅ **URL pública**: `/consultar/INF-PU-M-251112-001`
- ✅ **Sistema en producción**: ✅

---

## 🧪 Casos de Prueba

### Prueba 1: Búsqueda por Ticket
```
1. Navegar: http://localhost/consultar
2. Seleccionar: Radio "Número de Ticket"
3. Ingresar: INF-PU-M-251112-001
4. Click: "Buscar Mi Solicitud"
5. Verificar: Redirección a detalle con historial
```

### Prueba 2: Búsqueda por Email
```
1. Navegar: http://localhost/consultar
2. Seleccionar: Radio "Correo Electrónico"
3. Ingresar: usuario@dominio.com
4. Click: "Buscar Mi Solicitud"
5. Verificar: Lista de solicitudes del usuario
```

### Prueba 3: Compartir por WhatsApp
```
1. Abrir detalle de solicitud
2. Click: Botón "Compartir por WhatsApp"
3. Verificar: Abre WhatsApp Web con mensaje formateado
4. Verificar: Incluye ticket, estado, URL
```

### Prueba 4: Copiar Enlace
```
1. Abrir detalle de solicitud
2. Click: Botón "Copiar Enlace"
3. Verificar: Notificación verde aparece
4. Pegar: URL copiada funciona
```

### Prueba 5: Historial de Estados
```
1. Cambiar estado de una solicitud en sistema autenticado
2. Navegar: Vista pública de esa solicitud
3. Verificar: Nuevo estado aparece en historial
4. Verificar: Muestra quién cambió y cuándo
```

---

## 🔐 Seguridad

✅ **Sin Autenticación**
- No requiere login
- Acceso público controlado

✅ **Solo Lectura**
- No permite modificaciones
- Vista informativa únicamente

✅ **Validación de Inputs**
- Sanitización de búsquedas
- Protección contra inyecciones

✅ **Protección CSRF**
- Tokens en formularios POST
- Validación de origen

✅ **Rate Limiting**
- 60 peticiones por minuto
- Previene abuso

✅ **No Expone Datos Sensibles**
- Solo información pública
- Sin datos internos del sistema

---

## 🎯 Casos de Uso Reales

### Caso 1: Usuario Sin Cuenta
```
Contexto: Usuario externo reporta problema por teléfono
Flujo:
1. Soporte crea solicitud en su nombre
2. Usuario recibe ticket por SMS/Email
3. Consulta estado en /consultar sin registrarse
4. Ve progreso en tiempo real
5. Comparte con colegas si necesario
```

### Caso 2: Usuario Registrado (Acceso Rápido)
```
Contexto: Usuario con cuenta prefiere no iniciar sesión
Flujo:
1. Recuerda su ticket o email
2. Consulta en página pública
3. Ve historial completo de cambios
4. Verifica quién atendió su caso
5. Sin necesidad de login
```

### Caso 3: Soporte al Cliente
```
Contexto: Reducir carga de llamadas de consulta
Flujo:
1. Cliente llama preguntando estado
2. Soporte comparte enlace público
3. Cliente ve información actualizada
4. Puede compartir internamente
5. Reduce tiempo de atención
```

### Caso 4: Transparencia Organizacional
```
Contexto: Empresa requiere visibilidad de solicitudes
Flujo:
1. Gerencia solicita reporte
2. Solicitud se busca por email corporativo
3. Ve todas las solicitudes del departamento
4. Accede a historial completo
5. Copia enlaces para reportes
```

---

## 📝 Notas Técnicas

### Eager Loading Optimizado
```php
$serviceRequest = ServiceRequest::with([
    'subService.service',
    'requester',
    'assignee',
    'statusHistories' => function($query) {
        $query->with('changedBy')
              ->orderBy('created_at', 'desc');
    }
])->where('ticket_number', $ticketNumber)
  ->firstOrFail();
```

### Performance
- ✅ Índices en campos de búsqueda
- ✅ Paginación de resultados (15/página)
- ✅ Eager loading previene N+1 queries
- ✅ CDN para assets estáticos
- ✅ Cache de vistas compiladas

### Accessibilidad (WCAG 2.1)
- ✅ Contraste de colores AA
- ✅ Navegación por teclado
- ✅ Atributos ARIA
- ✅ Textos alternativos
- ✅ Labels en formularios

---

## 🔧 Mantenimiento

### Limpiar Historiales Antiguos
```php
// En tinker o comando artisan
ServiceRequestStatusHistory::whereHas('serviceRequest', function($q) {
    $q->where('status', 'CERRADA')
      ->where('closed_at', '<', now()->subYears(2));
})->delete();
```

### Regenerar Historial
```bash
php artisan migrate:refresh --path=database/migrations/2025_11_16_180000_populate_initial_status_history.php
```

### Verificar Integridad
```bash
php artisan tinker

# Solicitudes sin historial
ServiceRequest::doesntHave('statusHistories')->count();

# Historiales huérfanos
ServiceRequestStatusHistory::whereDoesntHave('serviceRequest')->count();
```

---

## 📊 Mejoras Futuras (Roadmap)

- [ ] **Notificaciones por Email**
  - Envío automático cuando cambia estado
  - Suscripción opcional

- [ ] **Código QR**
  - Generar QR del enlace público
  - Fácil compartir en físico

- [ ] **Export PDF**
  - Descargar detalle completo
  - Incluir historial

- [ ] **Comentarios Públicos**
  - Permitir comentarios del usuario
  - Sin login requerido

- [ ] **Adjuntar Evidencias Públicas**
  - Usuario sube archivos adicionales
  - Validación de tipos de archivo

- [ ] **Estadísticas Públicas**
  - Tiempo promedio de resolución
  - Satisfacción del usuario

---

## 📞 Soporte y Recursos

**Contacto:**
- Email: soporte@weirdoware.com
- GitHub: [WilliamSotaquira/Sapp](https://github.com/WilliamSotaquira/Sapp)

**URLs Importantes:**
- Producción: https://sapp.weirdoware.com
- Consulta Pública: https://sapp.weirdoware.com/consultar
- Repositorio: https://github.com/WilliamSotaquira/Sapp

**Documentación Adicional:**
- `CONSULTA_PUBLICA_DOCUMENTACION.md` - Versión anterior
- `README.md` - Información general del proyecto
- `routes/web.php` - Definición de rutas

---

## 📅 Changelog

### v2.0.0 - 16 de noviembre de 2025
- ✅ Sistema completo de historial de estados
- ✅ Observer automático para tracking
- ✅ Mejoras visuales en todas las vistas
- ✅ Cards mejoradas con gradientes
- ✅ Ejemplos visuales en búsqueda
- ✅ Sección "¿Cómo funciona?"
- ✅ Timeline interactivo de historial
- ✅ Metadata completa de cambios
- ✅ 49 solicitudes con historial inicial

### v1.0.0 - 15 de noviembre de 2025
- ✅ Sistema básico de consulta
- ✅ Búsqueda por ticket/email
- ✅ Vista de detalle
- ✅ Botones de compartir
- ✅ Responsive design básico

---

**Última actualización**: 16 de noviembre de 2025  
**Versión**: 2.0.0  
**Estado**: ✅ Producción  
**Autor**: William Sotaquira  
**Licencia**: Propietaria
