# Solicitud (Modo Rapido)

## Disparador

Cuando se use la palabra `solicitud`, iniciar con:
+ `Agrega la solicitud.`

## Objetivo

Crear solicitudes rapido, con clasificacion correcta, trazabilidad y sin consultas manuales repetidas.

## Flujo unico (rapido)

1. Extraer: `solicitante`, `titulo`, `descripcion`, `canal`, `rutas web` (si existen).
2. Validar duplicado: confirmar que la solicitud no exista ya (mismo solicitante + asunto/titulo + fecha/contexto); si existe, no crear otra y devolver referencia.
3. Identificar entidad del solicitante.
4. Clasificar `sub_service_id` desde `CATALOGO_ACTIVO_POR_ENTIDAD.txt` usando solo `OMITIR=NO`.
5. Resolver/crear solicitante con `findOrCreateRequesterForCompany(...)`.
6. Resolver contexto tecnico con `resolveCreationContext(company_id, sub_service_id, criticality)`.
7. Crear solicitud con `createServiceRequest(...)`.

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
- Los titulos visibles de tareas/subtareas deben ser descriptivos y naturales (sin pipes `|`, sin prefijos tecnicos, sin metadatos incrustados).
- Las subtareas deben incluir el tiempo en el titulo al final, en formato: `(XX min)`.
- Funcion webmaster + practica ITIL deben ir en `description` (tarea) o `notes` (subtarea), sin sobrecargar el titulo.
- La `description` de tareas y las `notes` de subtareas deben ser detalladas en longitud media (contexto, objetivo, alcance y validacion esperada).

## Validacion obligatoria por entidad (no negociable)

- Antes de crear, validar coherencia completa contra `company_id` (entidad destino).
- `requester_id` debe pertenecer a la misma entidad (`requesters.company_id == company_id`).
- `sub_service_id` debe pertenecer al catalogo/contrato activo de esa entidad.
- `sla_id` debe corresponder exactamente al `sub_service_id` y a la entidad.
- Si cualquier validacion falla: **no crear** la solicitud y devolver error claro indicando el campo inconsistente.
- Esta regla aplica siempre: formulario web, API, scripts, carga masiva o creacion manual desde consola.
- Nunca reutilizar por defecto `requester_id`, `sub_service_id` o `sla_id` de una solicitud previa sin revalidar entidad.

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
- Mantener trazabilidad: registrar funcion webmaster + practica ITIL en `description/notes`.

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

- `Titulo tarea: Actualizar enlace del formulario de inscripcion de motociclistas`
- `Descripcion tarea: Aplicar el cambio solicitado con trazabilidad y validacion funcional de extremo a extremo. Funcion webmaster: Gestion de contenidos web (publicacion, actualizacion, retiro). ITIL: Gestion de Solicitudes de Servicio. Tiempo total estimado: 90 min.`
- `Titulo subtarea: Verificar enlace actualizado en entorno productivo (20 min)`
- `Notas subtarea: Validar el comportamiento del enlace en desktop y movil, confirmar redireccionamiento correcto y registrar evidencia de conformidad para cierre. Funcion webmaster: Administracion de estructura y navegacion (menus, enlaces, arquitectura). ITIL: Monitoreo y Gestion de Eventos.`

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
Descripcion tarea (funcion webmaster + ITIL + tiempo):
Notas subtareas (funcion webmaster + ITIL + tiempo):
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
