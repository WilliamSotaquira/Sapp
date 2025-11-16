# Sistema de Consulta Pública de Solicitudes

## Descripción General

Sistema implementado para permitir a los usuarios regulares consultar el estado de sus solicitudes de servicio sin necesidad de autenticarse en el sistema principal.

## Características Principales

### 1. **Consulta Pública Sin Autenticación**
- Los usuarios pueden buscar sus solicitudes sin necesidad de cuenta en el sistema
- Dos métodos de búsqueda disponibles:
  - **Por Número de Ticket**: Búsqueda directa usando el código único (ej: SR-2024-001)
  - **Por Correo Electrónico**: Muestra todas las solicitudes del email registrado

### 2. **Información Mostrada**

#### Vista de Detalle Individual
- **Encabezado**: Número de ticket y estado actual
- **Información General**:
  - Servicio y subservicio solicitado
  - Nivel de criticidad
  - Fecha de creación
- **Plazos y Tiempos**:
  - Plazo de aceptación
  - Plazo de respuesta
  - Plazo de resolución
- **Descripción**: Detalle completo de la solicitud
- **Historial**: Timeline de cambios de estado con comentarios

#### Vista de Lista (por Email)
- Tarjetas compactas con información resumida
- Estado visual con colores identificativos
- Criticidad y fecha de creación
- Acceso rápido al detalle de cada solicitud
- Paginación para múltiples solicitudes

### 3. **Estados Visualizados**

| Estado | Color | Icono | Descripción |
|--------|-------|-------|-------------|
| NUEVA | Azul | fa-star | Solicitud recién creada |
| EN_REVISION | Amarillo | fa-search | En proceso de revisión |
| ACEPTADA | Verde | fa-check | Aprobada para ejecución |
| EN_PROGRESO | Morado | fa-cog | En desarrollo |
| RESUELTA | Teal | fa-check-circle | Completada |
| CERRADA | Gris | fa-lock | Finalizada y archivada |
| RECHAZADA | Rojo | fa-times-circle | No aprobada |
| PAUSADA | Naranja | fa-pause-circle | Temporalmente detenida |

### 4. **Niveles de Criticidad**

| Nivel | Color | Icono |
|-------|-------|-------|
| BAJA | Verde | fa-arrow-down |
| MEDIA | Amarillo | fa-minus |
| ALTA | Naranja | fa-arrow-up |
| CRITICA | Rojo | fa-exclamation-triangle |

## Archivos Creados

### Controlador
- **`app/Http/Controllers/PublicTrackingController.php`**
  - `index()`: Muestra formulario de búsqueda
  - `search()`: Procesa búsqueda por ticket o email
  - `show()`: Muestra detalle de solicitud específica
  - `verifyEmail()`: Validación de acceso por email (futuro)

### Vistas
- **`resources/views/public/tracking/index.blade.php`**
  - Página principal con formulario de búsqueda
  - Selector de tipo de búsqueda (ticket/email)
  - Información de ayuda para usuarios

- **`resources/views/public/tracking/show.blade.php`**
  - Vista detallada de una solicitud individual
  - Timeline de historial de estados
  - Información completa de plazos y tiempos

- **`resources/views/public/tracking/list.blade.php`**
  - Lista de solicitudes encontradas por email
  - Tarjetas con información resumida
  - Paginación para múltiples resultados

### Rutas
- **`routes/web.php`**
  ```php
  Route::prefix('consultar')->name('public.tracking.')->group(function () {
      Route::get('/', [PublicTrackingController::class, 'index'])->name('index');
      Route::post('/buscar', [PublicTrackingController::class, 'search'])->name('search');
      Route::get('/{ticketNumber}', [PublicTrackingController::class, 'show'])->name('show');
      Route::post('/{ticketNumber}/verificar', [PublicTrackingController::class, 'verifyEmail'])->name('verify');
  });
  ```

## URLs de Acceso

### Página Principal
```
http://sapp.local:8000/consultar
https://sapp.weirdoware.com/consultar
```

### Búsqueda
```
POST /consultar/buscar
Parámetros:
  - query: string (número de ticket o email)
  - type: string (ticket|email)
```

### Ver Detalle
```
GET /consultar/{ticketNumber}
Ejemplo: /consultar/SR-2024-001
```

## Integración con Página de Bienvenida

Se agregó botón destacado en la página principal (`welcome.blade.php`):
```html
<a href="{{ route('public.tracking.index') }}" 
   class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg">
    <i class="fas fa-search mr-2"></i>Consultar mi Solicitud
</a>
```

## Seguridad

### Consideraciones Implementadas
- ✅ Sin autenticación requerida (acceso público)
- ✅ Búsqueda limitada a información pública (no datos sensibles)
- ✅ Validación de inputs en el controlador
- ✅ Uso de Eloquent ORM (previene SQL injection)

### Consideraciones Futuras (Opcional)
- Sistema de verificación por email para acceso a detalles completos
- Captcha para prevenir abuso de búsquedas
- Rate limiting en endpoints públicos
- Token temporal para compartir enlaces de seguimiento

## Flujo de Uso

### Escenario 1: Consulta por Ticket
1. Usuario accede a `/consultar`
2. Selecciona "Número de Ticket"
3. Ingresa el código (ej: SR-2024-001)
4. Sistema muestra detalle completo de la solicitud

### Escenario 2: Consulta por Email
1. Usuario accede a `/consultar`
2. Selecciona "Correo Electrónico"
3. Ingresa su email registrado
4. Sistema muestra lista de todas sus solicitudes
5. Usuario hace clic en "Ver Detalles" de cualquier solicitud

## Diseño Responsive

- ✅ Mobile-first design con TailwindCSS
- ✅ Adaptación automática a diferentes tamaños de pantalla
- ✅ Iconos FontAwesome para mejor experiencia visual
- ✅ Cards con hover effects
- ✅ Diseño limpio y profesional

## Mensajes de Error

- **Ticket no encontrado**: "No se encontró ninguna solicitud con ese número de ticket."
- **Email sin resultados**: "No se encontraron solicitudes para ese correo electrónico."
- **Validación fallida**: Mensajes específicos por campo

## Próximas Mejoras Sugeridas

1. **Notificaciones por Email**
   - Enviar email al crear solicitud con link de seguimiento
   - Notificar cambios de estado importantes

2. **Exportación de Información**
   - Generar PDF del estado actual
   - Exportar historial completo

3. **Chat de Soporte**
   - Integrar chat en la vista de detalle
   - Permitir comunicación directa con el equipo

4. **Sistema de Calificación**
   - Permitir calificar el servicio desde la consulta pública
   - Encuestas de satisfacción

5. **Multiidioma**
   - Soporte para inglés y otros idiomas
   - Detección automática de idioma del navegador

## Pruebas Recomendadas

- [ ] Búsqueda por ticket válido
- [ ] Búsqueda por ticket inexistente
- [ ] Búsqueda por email con múltiples solicitudes
- [ ] Búsqueda por email sin solicitudes
- [ ] Responsive en móvil, tablet y desktop
- [ ] Validación de campos vacíos
- [ ] Paginación con más de 10 resultados
- [ ] Historial de estados en orden cronológico
