# 📋 MÓDULO DE TIEMPOS Y CAPACIDAD PARA TÉCNICOS

## ✅ ESTADO DE IMPLEMENTACIÓN

### ✔️ Completado

#### 1. Base de Datos
- ✅ Migración completa con 11 tablas
- ✅ Relaciones entre modelos establecidas
- ✅ Índices para optimización
- ✅ Seeder con datos de ejemplo (5 técnicos)

#### 2. Modelos Eloquent
- ✅ `Technician` - Gestión de técnicos
- ✅ `Task` - Tareas de soporte/desarrollo
- ✅ `ScheduleBlock` - Bloques de horario
- ✅ `TaskHistory` - Historial de cambios
- ✅ `CapacityRule` - Reglas de capacidad
- ✅ `SlaCompliance` - Cumplimiento de SLA
- ✅ `TaskDependency` - Dependencias entre tareas
- ✅ `TechnicianSkill` - Skills técnicas
- ✅ `TaskGitAssociation` - Asociación con Git
- ✅ `KnowledgeBaseLink` - Base de conocimiento
- ✅ `EnvironmentAccess` - Accesos a ambientes

#### 3. Controladores
- ✅ `TechnicianController` - CRUD de técnicos
- ✅ `TaskController` - CRUD de tareas + workflow
- ✅ `TechnicianScheduleController` - Calendario y agenda

#### 4. Servicios
- ✅ `TaskAssignmentService` - Asignación inteligente de tareas

#### 5. Vistas
- ✅ Calendario (día/semana/mes)
- ✅ Mi Agenda (vista del técnico)
- ✅ Dashboard de capacidad del equipo
- ✅ Vistas parciales para cada tipo de vista

#### 6. Rutas
- ✅ Todas las rutas del módulo definidas
- ✅ Integración con `routes/web.php`

#### 7. Integración con Módulos Existentes
- ✅ ServiceRequest → tasks (relación)
- ✅ User → technician (relación)
- ✅ Project → tasks (relación)

---

## 🚀 CÓMO USAR EL MÓDULO

### 1. Acceder al Módulo

**URLs Principales:**
- Calendario: `/technician-schedule`
- Mi Agenda: `/technician-schedule/my-agenda`
- Capacidad del Equipo: `/technician-schedule/team-capacity`
- Gestión de Técnicos: `/technicians`
- Gestión de Tareas: `/tasks`

### 2. Crear un Técnico

```php
// Navegar a /technicians/create
// O programáticamente:
$technician = Technician::create([
    'user_id' => $user->id,
    'specialties' => ['Laravel', 'PHP', 'MySQL'],
    'experience_level' => 'senior',
    'remote_available' => true,
    'work_start_time' => '08:00',
    'work_end_time' => '17:00',
    'status' => 'active',
    'daily_capacity_minutes' => 480,
]);
```

### 3. Crear una Tarea

**Tarea de Impacto (Mañana - 90 min):**
```php
$task = Task::create([
    'type' => 'impact',
    'title' => 'Implementar nueva feature de pagos',
    'description' => 'Integración con pasarela de pagos',
    'service_request_id' => $serviceRequest->id,
    'scheduled_date' => now()->addDay(),
    'scheduled_time' => '08:15',
    'estimated_duration_minutes' => 90,
    'priority' => 'high',
    'technologies' => ['Laravel', 'Stripe API'],
]);
```

**Tarea Regular (Tarde - 25 min):**
```php
$task = Task::create([
    'type' => 'regular',
    'title' => 'Code review de PR #123',
    'scheduled_date' => now(),
    'scheduled_time' => '13:00',
    'estimated_duration_minutes' => 25,
    'priority' => 'medium',
]);
```

### 4. Asignar Tarea a Técnico

**Asignación Automática (Recomendado):**
```php
$assignmentService = app(TaskAssignmentService::class);
$result = $assignmentService->autoAssignTask($task);

if ($result['success']) {
    // Asignado exitosamente
    $technician = $result['technician'];
    $slot = $result['slot'];
    $score = $result['score'];
}
```

**Asignación Manual:**
```php
$task->update([
    'technician_id' => $technician->id,
    'scheduled_date' => '2025-11-16',
    'scheduled_time' => '10:00',
]);

$task->addHistory('assigned', auth()->id(), "Asignado manualmente");
```

### 5. Workflow de Tareas

**Iniciar Tarea:**
```php
$task->start();
// Actualiza: status => 'in_progress', started_at => now()
```

**Completar Tarea:**
```php
$task->complete('Bug resuelto aplicando parche en controlador');
// Actualiza: status => 'completed', completed_at => now()
```

**Bloquear Tarea:**
```php
$task->block('Esperando información del cliente');
// Actualiza: status => 'blocked', blocked_at => now()
```

**Desbloquear Tarea:**
```php
$task->unblock();
// Actualiza: status => 'pending', blocked_at => null
```

### 6. Modelo de Trabajo (2+6)

**Mañana - Deep Work (Tareas de Impacto):**
```
08:00 - 08:15 → Setup del día
08:15 - 09:45 → 🔴 TAREA IMPACTO #1 (90 min)
09:45 - 10:00 → Break
10:00 - 11:30 → 🔴 TAREA IMPACTO #2 (90 min)
11:30 - 13:00 → Code Review / Sync
```

**Tarde - Operational Work (Tareas Regulares):**
```
13:00 - 13:25 → 🟡 Tarea Regular #1 (25 min)
13:30 - 13:55 → 🟡 Tarea Regular #2 (25 min)
14:00 - 14:25 → 🟡 Tarea Regular #3 (25 min)
14:30 - 15:00 → Break / Reunión
15:00 - 15:25 → 🟡 Tarea Regular #4 (25 min)
15:30 - 15:55 → 🟡 Tarea Regular #5 (25 min)
16:00 - 16:25 → 🟡 Tarea Regular #6 (25 min)
16:30 - 17:00 → Documentación / Cierre
```

---

## 🔧 CONFIGURACIÓN

### Reglas de Capacidad

**Global (para todos los técnicos):**
```php
CapacityRule::create([
    'technician_id' => null, // null = global
    'day_type' => 'weekday',
    'max_impact_tasks_morning' => 2,
    'max_regular_tasks_afternoon' => 6,
    'impact_task_duration_minutes' => 90,
    'regular_task_duration_minutes' => 25,
    'is_active' => true,
]);
```

**Específica para un técnico:**
```php
CapacityRule::create([
    'technician_id' => $technician->id,
    'day_type' => 'weekday',
    'max_impact_tasks_morning' => 1, // Solo 1 tarea de impacto
    'max_regular_tasks_afternoon' => 4, // Solo 4 regulares
    'is_active' => true,
]);
```

### Skills de Técnicos

```php
TechnicianSkill::create([
    'technician_id' => $technician->id,
    'skill_name' => 'Laravel',
    'proficiency_level' => 'expert',
    'years_experience' => 5,
    'is_primary' => true,
]);
```

---

## 📊 REPORTES Y MÉTRICAS

### Métricas por Técnico

```php
$technician = Technician::find(1);

// Tareas completadas
$completed = $technician->tasks()->completed()->count();

// Tareas pendientes
$pending = $technician->tasks()->pending()->count();

// Tiempo promedio de ejecución
$avgTime = $technician->tasks()->completed()->avg('actual_duration_minutes');

// Capacidad disponible hoy
$availableCapacity = $technician->getAvailableCapacityForDate(now());
```

### Métricas del Equipo

```php
// Total de técnicos activos
$activeTechnicians = Technician::active()->count();

// Tareas del día
$tasksToday = Task::forDate(now())->count();

// Cumplimiento de SLA
$slaCompliance = SlaCompliance::whereHas('task', function($q) {
    $q->forDate(now());
})->where('compliance_status', 'within_sla')->count();
```

---

## 🔗 INTEGRACIÓN CON SERVICE REQUESTS

### Crear Tarea desde Service Request

```php
// En el controlador de ServiceRequest
$serviceRequest = ServiceRequest::find(1);

$task = Task::create([
    'type' => 'impact', // Determinar según criticality_level
    'title' => $serviceRequest->title,
    'description' => $serviceRequest->description,
    'service_request_id' => $serviceRequest->id,
    'sla_id' => $serviceRequest->sla_id,
    'priority' => $this->mapCriticalityToPriority($serviceRequest->criticality_level),
    'scheduled_date' => now()->addDay(),
    'scheduled_time' => '08:15',
]);

// Auto-asignar
$assignmentService = app(TaskAssignmentService::class);
$assignmentService->autoAssignTask($task);
```

### Actualizar Service Request al Completar Tarea

```php
$task->complete('Tarea completada exitosamente');

// Automáticamente actualiza el service request
$task->serviceRequest->updateStatusFromTasks();
```

---

## 📱 VISTAS DISPONIBLES

### 1. Calendario (`/technician-schedule`)
- Vista Día: Timeline detallado con bloques horarios
- Vista Semana: Cuadrícula de 7 días
- Vista Mes: Calendario mensual

**Filtros:**
- Por técnico
- Por fecha
- Por tipo de tarea

### 2. Mi Agenda (`/technician-schedule/my-agenda`)
- Vista personalizada para cada técnico
- Tareas del día ordenadas cronológicamente
- Acciones rápidas: Iniciar / Completar tarea
- Estadísticas del día

### 3. Capacidad del Equipo (`/technician-schedule/team-capacity`)
- Utilización por técnico
- Barras de progreso
- Alertas de sobrecarga
- Recomendaciones de balanceo

---

## 🎯 ALGORITMO DE ASIGNACIÓN INTELIGENTE

El `TaskAssignmentService` calcula un score (0-100) basado en:

1. **Skills Técnicas (30%):** Coincidencia con tecnologías requeridas
2. **Disponibilidad (25%):** Capacidad disponible
3. **Carga Actual (20%):** Número de tareas asignadas
4. **Experiencia (15%):** Trabajo previo en proyecto/cliente
5. **Complejidad vs Nivel (10%):** Match entre complejidad y experiencia

**Ejemplo de uso:**
```php
$suggestions = $assignmentService->suggestTechnicianForTask($task);

foreach ($suggestions as $suggestion) {
    echo "{$suggestion['technician']->user->name}: {$suggestion['score']} puntos\n";
    print_r($suggestion['reasons']);
}
```

---

## 🚨 VALIDACIONES

### Al Asignar Tarea

✅ Técnico está activo
✅ No excede límite de tareas de impacto (2)
✅ No excede límite de tareas regulares (6)
✅ Horario disponible (no hay superposición)
✅ Capacidad suficiente

### Al Crear Tarea

✅ Tipo válido (impact/regular)
✅ Fecha y hora válidas
✅ Duración apropiada según tipo
✅ Service Request existe (si aplica)

---

## 📝 DATOS DE EJEMPLO

### Técnicos Creados por el Seeder

1. **Juan Pérez** - Senior Backend (Laravel, PHP, MySQL, API REST)
2. **María García** - Senior Frontend (React, Vue.js, JavaScript, CSS)
3. **Carlos Rodríguez** - Mid Fullstack (Laravel, Vue.js, JavaScript, PostgreSQL)
4. **Ana Martínez** - Mid DevOps (Docker, Linux, CI/CD, AWS)
5. **Luis Fernández** - Junior Frontend (JavaScript, HTML/CSS, React)

**Credenciales:** `email` / `password123`

---

## 🔄 PRÓXIMOS PASOS SUGERIDOS

### Fase 2: Mejoras
- [ ] Integración con Google Calendar
- [ ] Notificaciones por email/SMS
- [ ] Reportes avanzados en PDF
- [ ] Dashboard con gráficos (Chart.js)
- [ ] Drag & Drop en calendario

### Fase 3: Automatización
- [ ] Auto-asignación al crear Service Request
- [ ] Alertas de SLA próximas a vencer
- [ ] Sugerencias de reprogramación
- [ ] Balanceo automático de carga

### Fase 4: Analítica
- [ ] Predicción de tiempos
- [ ] Análisis de productividad
- [ ] Identificación de cuellos de botella
- [ ] KPIs y tendencias

---

## 🆘 TROUBLESHOOTING

### Error: "No se puede asignar tarea"
- Verificar que el técnico esté activo
- Revisar capacidad disponible
- Comprobar reglas de capacidad

### Tareas no aparecen en calendario
- Verificar que tengan `scheduled_date` y `scheduled_time`
- Verificar filtros aplicados
- Comprobar estado de la tarea

### SLA no se calcula
- Verificar que la tarea tenga `sla_id`
- Comprobar que existe `SlaCompliance` record
- Ejecutar `$task->slaCompliance->calculateCompliance()`

---

## 📚 RECURSOS ADICIONALES

**Documentación de Modelos:**
- `app/Models/Technician.php`
- `app/Models/Task.php`
- `app/Services/TaskAssignmentService.php`

**Migraciones:**
- `database/migrations/2025_11_15_171500_create_technician_module_tables.php`

**Rutas:**
- `routes/features/technician-module/web.php`

**Vistas:**
- `resources/views/technician-schedule/`
- `resources/views/technicians/`
- `resources/views/tasks/`

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

- [x] Base de datos migrada
- [x] Modelos creados
- [x] Controladores implementados
- [x] Rutas definidas
- [x] Vistas principales creadas
- [x] Servicio de asignación implementado
- [x] Integración con módulos existentes
- [x] Seeder con datos de ejemplo
- [x] Documentación completa

---

**Módulo listo para usar! 🎉**

Para comenzar:
1. Navega a `/technicians` para ver los técnicos
2. Ve a `/technician-schedule` para ver el calendario
3. Accede a `/technician-schedule/my-agenda` como técnico

¿Preguntas? Revisa esta documentación o consulta el código fuente.
