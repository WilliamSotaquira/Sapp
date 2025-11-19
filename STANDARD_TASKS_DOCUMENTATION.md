# Sistema de Tareas Predefinidas

## 🎯 Descripción
Sistema completo de gestión de tareas estándar (plantillas) asociadas a subservicios. Incluye:
- ✅ CRUD completo de tareas predefinidas con interfaz web
- ✅ Gestión de subtareas predefinidas
- ✅ Asignación automática al crear solicitudes de servicio
- ✅ API REST para integración con formularios
- ✅ Filtros y búsqueda avanzada
- ✅ Estadísticas en tiempo real

## 📍 Accesos Rápidos

### Interfaz Web
- **Lista de Tareas**: `/standard-tasks` → Ver, filtrar y gestionar
- **Nueva Tarea**: `/standard-tasks/create` → Crear plantilla
- **Editar**: `/standard-tasks/{id}/edit` → Modificar plantilla
- **Detalle**: `/standard-tasks/{id}` → Ver información completa

### Navegación
**Menú Principal → Técnicos → Tareas Predefinidas**

## Estructura de Base de Datos

### Tabla: `standard_tasks`
Almacena las tareas plantilla asociadas a cada subservicio.

**Campos principales:**
- `sub_service_id`: Relación con el subservicio
- `title`: Título de la tarea
- `description`: Descripción detallada
- `type`: 'impact' o 'regular'
- `priority`: 'critical', 'high', 'medium', 'low'
- `estimated_hours`: Horas estimadas (decimal)
- `technical_complexity`: Nivel 1-5
- `technologies`, `required_accesses`, `environment`, `technical_notes`: Detalles técnicos
- `is_active`: Activa/inactiva
- `order`: Orden de ejecución

### Tabla: `standard_subtasks`
Almacena las subtareas de cada tarea predefinida.

**Campos principales:**
- `standard_task_id`: Relación con la tarea estándar
- `title`: Título de la subtarea
- `description`: Descripción
- `priority`: 'high', 'medium', 'low'
- `is_active`: Activa/inactiva
- `order`: Orden

## Modelos

### `StandardTask`
```php
// Relaciones
$task->subService()           // SubService al que pertenece
$task->standardSubtasks()     // Subtareas predefinidas

// Scopes
StandardTask::active()        // Solo tareas activas
StandardTask::forSubService($id) // Por subservicio
StandardTask::ordered()       // Ordenadas
```

### `StandardSubtask`
```php
// Relaciones
$subtask->standardTask()      // Tarea estándar padre

// Scopes
StandardSubtask::active()     // Solo activas
StandardSubtask::ordered()    // Ordenadas
```

### `SubService` (actualizado)
```php
$subService->standardTasks()  // Tareas predefinidas activas y ordenadas
```

## Endpoint API

### GET `/api/sub-services/{id}/standard-tasks`
Obtiene todas las tareas predefinidas de un subservicio con sus subtareas.

**Respuesta:**
```json
[
  {
    "id": 1,
    "sub_service_id": 1,
    "title": "Diagnóstico del error reportado",
    "description": "Identificar y documentar el error",
    "type": "regular",
    "priority": "high",
    "estimated_hours": "0.50",
    "technical_complexity": null,
    "order": 1,
    "standard_subtasks": [
      {
        "id": 1,
        "title": "Revisar contenido afectado",
        "priority": "high",
        "order": 1
      }
    ]
  }
]
```

## Flujo de Uso

### 1. Crear Tareas Predefinidas (Una vez)
```php
$task = StandardTask::create([
    'sub_service_id' => 1,
    'title' => 'Análisis de requerimientos',
    'priority' => 'high',
    'estimated_hours' => 2.0,
]);

$task->standardSubtasks()->create([
    'title' => 'Reunión con solicitante',
    'priority' => 'high',
]);
```

### 2. Crear Solicitud con Tareas
1. Usuario selecciona subservicio en formulario
2. Sistema carga tareas predefinidas vía AJAX
3. Usuario marca checkbox "Crear tareas automáticamente"
4. Al guardar, se crean automáticamente:
   - Todas las tareas del subservicio
   - Todas las subtareas de cada tarea
   - Se asignan al técnico si la solicitud ya tiene uno asignado
   - Se configuran con fecha de inicio mañana a las 8:00

## Subservicios con Tareas Predefinidas

### 1. Error o Problema con Contenido Publicado (ERROR_CONTENIDO)
- 3 tareas
- 8 subtareas total
- Tiempo estimado: 2 horas

### 2. Solicitud de Publicación (SOL_PUBLICACION)
- 3 tareas
- 10 subtareas total
- Tiempo estimado: 1.75 horas

### 3. Desarrollo Técnico (DESARROLLO_TECNICO)
- 4 tareas
- 14 subtareas total
- Tiempo estimado: 15 horas

## Archivos Modificados/Creados

### Migraciones
- `2025_11_19_045409_create_standard_tasks_table.php`
- `2025_11_19_045433_create_standard_subtasks_table.php`

### Modelos
- `app/Models/StandardTask.php` (nuevo)
- `app/Models/StandardSubtask.php` (nuevo)
- `app/Models/SubService.php` (actualizado - añadida relación)

### Seeders
- `database/seeders/StandardTaskSeeder.php` (ya existía, actualizado)

### Rutas
- `routes/web-api.php` (añadido endpoint)

### Controladores
- `app/Http/Controllers/ServiceRequestController.php` (añadido método `createStandardTasksForRequest`)

### Vistas
- `resources/views/service-requests/create.blade.php` (añadida sección de tareas predefinidas + JavaScript)

## Comandos Útiles

```bash
# Migrar tablas
php artisan migrate

# Poblar con datos de ejemplo
php artisan db:seed --class=StandardTaskSeeder

# Ver tareas predefinidas
php test-standard-tasks.php
```

## Próximos Pasos Recomendados

1. **Interfaz de administración**: Crear CRUD para gestionar tareas predefinidas desde el panel
2. **Más plantillas**: Añadir tareas predefinidas para más subservicios
3. **Personalización**: Permitir editar tareas antes de crearlas
4. **Reportes**: Analizar qué tareas predefinidas se usan más
5. **Versioning**: Mantener historial de cambios en las plantillas
