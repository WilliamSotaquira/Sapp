# Solicitud (Modo Rapido)

## Disparador

Cuando se use la palabra `solicitud`, iniciar con:
+ `Agrega la solicitud.`

## Objetivo

Crear solicitudes rapido, con clasificacion correcta, trazabilidad y sin consultas manuales repetidas.

## Flujo unico (rapido)

1. Extraer: `solicitante`, `titulo`, `descripcion`, `canal`, `rutas web` (si existen).
2. Identificar entidad del solicitante.
3. Clasificar `sub_service_id` desde `CATALOGO_ACTIVO_POR_ENTIDAD.txt` usando solo `OMITIR=NO`.
4. Resolver/crear solicitante con `findOrCreateRequesterForCompany(...)`.
5. Resolver contexto tecnico con `resolveCreationContext(company_id, sub_service_id, criticality)`.
6. Crear solicitud con `createServiceRequest(...)`.

## Reglas fijas

- `requested_by` siempre William (`id=3`).
- `assigned_to` siempre William (`id=3`).
- `cut_id` siempre el mas reciente (lo devuelve `resolveCreationContext`).
- `entry_channel` se infiere del contenido: correo=`email_corporativo`, WhatsApp=`whatsapp`, llamada=`telefono`, reunion=`reunion`.
- `web_routes` es opcional; solo se registra si viene en el correo.
- Debe existir al menos 1 tarea con subtareas.
- Cada tarea/subtarea debe incluir tiempo en minutos.
- Cada tarea y subtarea debe estar asociada a una funcion de webmaster.
- Cada tarea y subtarea debe estar etiquetada y redactada bajo enfoque ITIL (practica/proceso aplicable).
- Subtareas: solo estrategicas (agrupadas por fases clave, sin micro-pasos operativos).

## Funciones webmaster permitidas (obligatorio)

- Gestion de contenidos web (publicacion, actualizacion, retiro).
- Administracion de estructura y navegacion (menus, enlaces, arquitectura).
- Gestion SEO tecnico y metadatos.
- Monitoreo y disponibilidad del sitio.
- Gestion de accesibilidad y usabilidad.
- Coordinacion de cambios y despliegues web.
- Gestion de incidencias y solicitudes web.
- Revision de analitica y mejora continua.

## Enfoque ITIL (obligatorio)

- Clasificar cada tarea/subtarea en al menos una practica ITIL: `Gestion de Incidentes`, `Gestion de Solicitudes de Servicio`, `Habilitacion de Cambios`, `Gestion de Problemas`, `Gestion de Niveles de Servicio`, `Monitoreo y Gestion de Eventos`, `Gestion del Conocimiento`.
- Redactar el objetivo de la tarea con lenguaje de valor del servicio (resultado para usuario/negocio).
- Mantener trazabilidad: incluir funcion webmaster + practica ITIL en el nombre de cada tarea/subtarea.

## Campos minimos para crear

- `company_id`
- `requester_id`
- `title`
- `description`
- `family_id`
- `service_id`
- `sub_service_id`
- `sla_id`
- `criticality_level`
- `cut_id`
- `requested_by`
- `assigned_to`
- `entry_channel`
- `tasks`

## Formato de tiempos

- `Tarea principal | Funcion webmaster: Gestion de contenidos | ITIL: Gestion de Solicitudes de Servicio (90 min)`
- `Subtarea estrategica | Funcion webmaster: SEO tecnico | ITIL: Habilitacion de Cambios (20 min)`

## Plantilla rapida

```txt
Solicitante:
Titulo:
Descripcion:
Entidad:
Subservicio (id/codigo):
Criticidad:
Canal:
Rutas web (opcional):
Tarea principal (min):
Subtareas (min):
```

## Metodos obligatorios (optimizacion)

- `ServiceRequestService::findOrCreateRequesterForCompany(...)`
- `ServiceRequestService::resolveCreationContext(...)`
- `ServiceRequestService::createServiceRequest(...)`

## Resultado esperado

Al finalizar, devolver:
- `service_request_id`
- `ticket_number`
- `status`
- `sub_service_id`
- `sla_id`
- `cut_id`
- `task_count`
- `subtask_count`
