# Vinculacion de solicitudes con cortes

## Regla vigente

Las solicitudes no se asignan manualmente a un corte desde los formularios de solicitud.

El corte asociado se calcula con la fecha de creacion de la solicitud (`service_requests.created_at`) y el rango configurado en el corte (`cuts.start_date` / `cuts.end_date`). La asociacion queda persistida en la tabla pivote `cut_service_request` para mantener compatibilidad con reportes y consultas existentes.

## Flujo principal

1. Al crear una solicitud, el sistema busca el corte del contrato correspondiente cuya fecha inicio/fin contenga la fecha de creacion.
2. Si existe un corte compatible, la solicitud queda asociada a ese corte.
3. Si no existe un corte compatible, la solicitud queda sin corte asociado.
4. Al editar una solicitud, la fecha de creacion puede ajustarse en la vista de editar.
5. Despues de guardar la edicion, el sistema recalcula automaticamente el corte por la nueva fecha de creacion.

## Recalculo desde cortes

Al crear, editar o recalcular un corte, el sistema sincroniza las solicitudes del contrato que fueron creadas dentro del rango del corte.

Los cortes del mismo contrato no pueden solaparse. Esto mantiene una asociacion exclusiva: una solicitud solo debe quedar en el corte que contiene su fecha de creacion.

## Interfaces afectadas

- El formulario de nueva solicitud ya no muestra selector de corte.
- El formulario de edicion muestra `Fecha de creacion`; modificarla recalcula el corte asociado.
- La tarjeta de detalle de solicitud muestra el corte como dato calculado.
- La vista de cortes permite revisar y recalcular solicitudes por fecha, no adjuntar o remover solicitudes manualmente.

## Comandos y flujos automaticos

El comando `service-requests:create-fast` puede recibir `source_date`. Esa fecha se usa como fecha de creacion de la solicitud y, por tanto, como base para resolver el corte.

Si no se envia `source_date`, se usa la fecha actual de creacion.

## Verificacion recomendada

```bash
php artisan test tests/Feature/ServiceRequests/ServiceRequestCutAssignmentByCreationDateTest.php
php artisan test tests/Feature/Reports/CutValidationTest.php
php artisan test tests/Feature/ServiceRequests/CreateFastServiceRequestCommandTest.php
```
