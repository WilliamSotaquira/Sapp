# Solicitud (Modo Rapido) - Tarea Global

## Alcance global

Esta tarea aplica de forma global para todo el proyecto `sapp`.
Siempre que se use `crear solicitud`, `solicitud`, `requerimiento` o `+`, se debe ejecutar este flujo.

## Disparador

Cuando se use cualquiera de estos términos: `crear solicitud`, `solicitud`, `requerimiento`, `+`, iniciar con: `Agrega la solicitud.`

Nota: para evitar falsos positivos, cuando se use `+` se interpreta como disparador solo si aparece como mensaje independiente o al inicio del mensaje.

## Objetivo

Crear solicitudes rapido, con clasificacion correcta, trazabilidad y sin consultas manuales repetidas.

## Principio de velocidad segura

- Optimizar secuencia, no omitir validaciones.
- Cortar temprano cuando haya duplicado confirmado o entidad/subservicio invalido.
- Reutilizar datos ya resueltos del mismo solicitante o entidad solo despues de revalidar coherencia.
- Hacer una sola pasada de extraccion del mensaje y usar ese resultado para todo el flujo.
- Evitar preguntas al usuario si la informacion puede inferirse con alta confianza desde el correo, remitente o contexto interno.

## Flujo unico (rapido)

1. Extraer en una sola pasada: `solicitante`, `email` si existe, `titulo`, `descripcion`, `canal`, `rutas web`, `fecha/contexto`, palabras clave funcionales.
2. Identificar entidad del solicitante desde email, historial o contexto organizacional.
3. Validar duplicado de forma temprana: mismo `company_id` + mismo solicitante + asunto similar + misma fecha o mismo hecho generador; si existe, no crear otra y devolver referencia.
4. Clasificar `sub_service_id` desde `CATALOGO_ACTIVO_POR_ENTIDAD.txt` usando solo `OMITIR=NO` y seleccionando la opcion mas especifica, no la mas general.
5. Resolver/crear solicitante con `findOrCreateRequesterForCompany(...)`.
6. Resolver contexto tecnico con `resolveCreationContext(company_id, sub_service_id, criticality)`.
7. Construir tareas y subtareas en bloque, con tiempos y redaccion final listos antes de crear.
8. Crear solicitud con `createServiceRequest(...)`.

## Orden recomendado para hacerlo mas rapido

1. Extraer.
2. Entidad.
3. Duplicado.
4. Subservicio.
5. Solicitante.
6. Contexto tecnico.
7. Tareas/subtareas.
8. Creacion.

Este orden reduce retrabajo porque evita redactar tareas o resolver SLA si la solicitud ya existe o si la entidad no corresponde.

## Reglas fijas

- `requested_by` siempre William (`id=3`).
- `assigned_to` siempre William (`id=3`).
- `cut_id` siempre el mas reciente (lo devuelve `resolveCreationContext`).
- `entry_channel` se infiere del contenido: correo=`email_corporativo`, WhatsApp=`whatsapp`, llamada=`telefono`, reunion=`reunion`.
- `web_routes` es opcional; solo se registra si viene en el correo.
- Debe existir al menos 1 tarea con subtareas.
- Cada tarea/subtarea debe incluir tiempo en minutos.
- Cada tarea y subtarea debe estar asociada a una funcion de webmaster.
- Cada tarea y subtarea debe redactarse con enfoque de servicio tecnico aplicable, integrado de forma natural en el texto (sin prefijos o etiquetas literales).
- Subtareas: estrategicas, descriptivas y completas; agrupadas por fases clave (sin micro-pasos operativos) y sin exceder la longitud maxima del campo.
- Los titulos visibles de tareas/subtareas deben ser descriptivos y naturales (sin pipes `|`, sin prefijos tecnicos, sin metadatos incrustados).
- Las subtareas deben incluir el tiempo en el titulo al final, en formato: `(XX min)`.
- La funcion webmaster y el enfoque de servicio tecnico deben quedar integrados en `description` (tarea) o `notes` (subtarea), sin sobrecargar el titulo.
- La `description` de tareas y las `notes` de subtareas deben ser detalladas en longitud media (contexto, objetivo, alcance y validacion esperada).
- Si faltan detalles menores no bloqueantes, usar la inferencia mas conservadora y dejar trazabilidad en la descripcion en vez de detener el flujo.

## Validacion obligatoria por entidad (no negociable)

- Antes de crear, validar coherencia completa contra `company_id` (entidad destino).
- `requester_id` debe pertenecer a la misma entidad (`requesters.company_id == company_id`).
- `sub_service_id` debe pertenecer al catalogo/contrato activo de esa entidad.
- `sla_id` debe corresponder exactamente al `sub_service_id` y a la entidad.
- Si cualquier validacion falla: **no crear** la solicitud y devolver error claro indicando el campo inconsistente.
- Esta regla aplica siempre: formulario web, API, scripts, carga masiva o creacion manual desde consola.
- Nunca reutilizar por defecto `requester_id`, `sub_service_id` o `sla_id` de una solicitud previa sin revalidar entidad.

## Criterios de inferencia rapida

- `entry_channel`: inferir sin preguntar desde el origen del mensaje.
- `criticality_level`: usar `MEDIA` por defecto si el mensaje no expresa urgencia operativa, vencimiento inmediato, caida o impacto alto.
- `web_routes`: registrar solo URLs explicitamente mencionadas; no inventar rutas.
- `title`: resumir el pedido en una accion concreta y visible.
- `description`: conservar el problema, objetivo y resultado esperado sin copiar ruido del hilo.

## Clasificacion rapida de subservicio

- Si el pedido trata de titulos, descripciones, indexacion, metadatos, snippets, buscadores o visibilidad organica: priorizar `SEO_TEC`.
- Si el pedido trata de experimentacion, comparativas, pruebas de comportamiento, rendimiento o mejoras iterativas de conversion/usabilidad con componente SEO: evaluar `AB_SEO`.
- Si hay duda entre dos subservicios validos, elegir el mas cercano al entregable inmediato solicitado y no al posible trabajo futuro derivado.

## Regla anti-retrabajo

- No rehacer clasificacion, tareas o contexto tecnico si ya fueron resueltos y siguen siendo coherentes con entidad, criticidad y alcance.
- Solo recalcular cuando cambie el solicitante, la entidad, el subservicio o la criticidad.
- Si el correo contiene varios pedidos distintos, dividir solo cuando impliquen entregables tecnicos diferentes; si no, consolidar en una sola solicitud.

## Funciones webmaster permitidas (obligatorio)

- Gestion de contenidos web (publicacion, actualizacion, retiro).
- Administracion de estructura y navegacion (menus, enlaces, arquitectura).
- Gestion SEO tecnico y metadatos.
- Monitoreo y disponibilidad del sitio.
- Gestion de accesibilidad y usabilidad.
- Coordinacion de cambios y despliegues web.
- Gestion de incidencias y solicitudes web.
- Revision de analitica y mejora continua.

## Enfoque de servicio tecnico (obligatorio)

- Clasificar cada tarea/subtarea en una practica de servicio tecnico aplicable: `gestion de incidentes`, `gestion de solicitudes de servicio`, `habilitacion de cambios`, `gestion de problemas`, `gestion de niveles de servicio`, `monitoreo y gestion de eventos`, `gestion del conocimiento`.
- Redactar el objetivo de la tarea con lenguaje de valor del servicio (resultado para usuario/negocio).
- Mantener trazabilidad: registrar funcion webmaster y practica aplicable de servicio tecnico en `description/notes`, sin usar etiquetas literales.

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
- `Descripcion tarea: Aplicar el cambio solicitado con trazabilidad y validacion funcional de extremo a extremo, desde la gestion de contenidos web y la atencion de solicitudes de servicio. Tiempo total estimado: 90 min.`
- `Titulo subtarea: Verificar enlace actualizado en entorno productivo (20 min)`
- `Notas subtarea: Validar el comportamiento del enlace en desktop y movil, confirmar redireccionamiento correcto y registrar evidencia de conformidad para cierre, con enfoque de administracion de estructura web y monitoreo funcional posterior al cambio.`

## Plantilla rapida

```txt
Solicitante:
Email (opcional):
Titulo:
Descripcion:
Fecha/contexto:
Entidad:
Subservicio (id/codigo):
Criticidad:
Canal:
Rutas web (opcional):
Tarea principal (min):
Subtareas (min):
Descripcion tarea (funcion webmaster + enfoque de servicio tecnico + tiempo):
Notas subtareas (funcion webmaster + enfoque de servicio tecnico + tiempo):
```

## Checklist minimo antes de crear

- Entidad validada.
- Duplicado descartado.
- `sub_service_id` activo y permitido para la entidad.
- `requester_id` perteneciente a la entidad.
- `sla_id` coherente con subservicio y criticidad.
- Al menos 1 tarea con subtareas y tiempos.
- Titulo y descripcion claros, sin ruido del correo.

## Metodos obligatorios (optimizacion)

- `ServiceRequestService::findOrCreateRequesterForCompany(...)`
- `ServiceRequestService::resolveCreationContext(...)`
- `ServiceRequestService::createServiceRequest(...)`

## Resultado esperado

Al finalizar, devolver:
- `service_request_id`
- `ticket_number`
- `status`
- `clipboard_copied`
- `sub_service_id`
- `sla_id`
- `cut_id`
- `task_count`
- `subtask_count`

## Salida recomendada

```txt
Solicitud creada:
- service_request_id:
- ticket_number:
- status:
- clipboard_copied:
- sub_service_id:
- sla_id:
- cut_id:
- task_count:
- subtask_count:
```

## Ejecucion por consola

Comando recomendado para acelerar la creacion sin saltarse validaciones:

```bash
php artisan service-requests:create-fast --file=storage/app/request.json
```

Tambien admite JSON inline:

```bash
php artisan service-requests:create-fast --json="{\"company_name\":\"Movilidad\",\"sub_service_code\":\"SEO_TEC\",\"requester_name\":\"Jimena Delgado Soto\",\"requester_email\":\"jdelgados@movilidadbogota.gov.co\",\"title\":\"Posicionamiento SEO OMB\",\"description\":\"Revisar por que el Observatorio de Movilidad no aparece al buscarlo y definir acciones de mejora SEO.\",\"criticality_level\":\"MEDIA\",\"entry_channel\":\"email_corporativo\",\"tasks\":[{\"title\":\"Analizar visibilidad organica del observatorio\",\"description\":\"Realizar diagnostico inicial de indexacion, metadatos y hallazgos priorizados con enfoque de SEO tecnico y gestion de solicitudes de servicio.\",\"priority\":\"medium\",\"type\":\"regular\",\"subtasks\":[{\"title\":\"Validar indexacion y presencia actual en buscadores (20 min)\",\"notes\":\"Confirmar si el sitio esta siendo indexado, revisar consultas basicas y registrar hallazgos para priorizacion.\",\"priority\":\"medium\",\"estimated_minutes\":20},{\"title\":\"Revisar metadatos y señales tecnicas clave (25 min)\",\"notes\":\"Verificar titulos, descripciones, encabezados y otros elementos tecnicos relevantes para visibilidad organica.\",\"priority\":\"medium\",\"estimated_minutes\":25}]}]}"
```

Opciones utiles:

- `--dry-run`: valida, resuelve contexto y detecta duplicados sin crear.
- `--allow-duplicate`: permite crear aunque exista una solicitud similar.
