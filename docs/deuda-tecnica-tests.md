# Deuda técnica: estado de la suite de tests

Documento de decisiones sobre los fallos preexistentes de la suite de pruebas.
Registra qué se corrigió, qué queda pendiente y por qué se decidió no abordarlo
por ahora.

## Contexto

Al retomar el trabajo sobre la rama `feature/operational-alerts-system`, la suite
estaba en **1197 passed / 53 failed**. Ninguno de esos fallos fue introducido por
el trabajo de features (sidebar, cola de trabajo, mejoras de Inicio, etc.); eran
**preexistentes**. Se decidió reducirlos priorizando arreglos de **raíz** y de
**bajo riesgo** que además mejoraran el código de producción, evitando parches
frágiles test-por-test.

Estado final de esta tanda: **1230 passed / 20 failed** (33 tests recuperados).

## Corregido (arreglos de raíz, ya en la rama)

| Área | Causa raíz | Corrección | Beneficio en producción |
|---|---|---|---|
| Arranque / bootstrap | `routes/console.php` consultaba `system_settings` al registrar el schedule; rompía el arranque con la tabla ausente (migraciones frescas, tests). | Leer la hora solo si la tabla existe, con default seguro. | Sí: arranque robusto en BD nueva. |
| Migraciones | Data-migration sembraba `standard_tasks` con `sub_service_id` fijos (190, 182) violando la FK en BD sin esos datos. | Guarda: sembrar solo si el subservicio existe. | Sí: migración idempotente y segura en cualquier entorno. |
| Migraciones | `add_contract_id_to_service_requests` usaba `UPDATE ... JOIN` (MySQL) sin ramificar por driver. | Rama SQLite con subconsultas correlacionadas, igual que las migraciones hermanas. | Sí: portabilidad de motor. |
| Reportes / Cortes | SQL crudo con `LEAST(...)` (MySQL) no soportado por SQLite, en 4 archivos (~10 usos). | Helper `App\Support\SqlExpr::effectiveCloseDate()` que emite `LEAST` (MySQL) o `MIN` (SQLite). | Sí: expresión centralizada y portable. |
| Workspace / navegación | El middleware `EnsureWorkspaceSelected` (web global) desviaba a `workspaces.select` a cualquier usuario sin contrato, incluso en flujos que no dependen de una entidad. | Eximir `password.*`, `verification.*`, `settings.*` y el POST de `confirm-password`. | **Sí, corrige bug real**: el usuario ya no es desviado al confirmar contraseña, verificar email o entrar a configuración. |
| Setup de test | `RequesterResolverTest` usaba `company_id` 1 y 2 fijos sin crear las companies. | Crear companies id 1 y 2 en `setUp()`. | No (solo test). |

## Pendiente (20 fallos) — DECISIÓN: no abordar por ahora

Se decidió **detener aquí** la reducción de deuda porque los fallos restantes
tienen peor relación esfuerzo/riesgo/valor. Detalle y motivo por grupo:

### 1. `EvidenceOrganizer*` (~6 fallos) — entorno Windows
- **Síntoma:** aserciones sobre rutas y archivos en `%TEMP%` de Windows (directorios/ficheros que no se crean/encuentran).
- **Naturaleza:** dependiente del entorno de archivos local, no de lógica de negocio.
- **Motivo de no abordar:** requiere revisar el manejo de paths del servicio (separadores, permisos temporales en Windows) con riesgo medio y valor bajo (no afecta la operación real). Candidato a revisar si se estandariza el entorno de test.

### 2. `ServiceRequestPlainTextPrefillTest` (7 fallos) — lógica del parser
- **Síntoma:** aserciones sobre el parseo de texto libre a solicitud (subservicio inferido, SLA, fechas, hilos de correo).
- **Naturaleza:** lógica de negocio compleja del intérprete de texto.
- **Motivo de no abordar:** alto riesgo de alterar comportamiento real de producción si se toca sin entender a fondo la lógica. Podría tratarse de fragilidad del test (datos de ejemplo) más que de un bug. Requiere investigación dedicada y confirmación de que existe un defecto real antes de modificar.

### 3. Cortes: `ServiceRequestCutAssignmentByTechnicianAssignmentDateTest` (3) y `CutValidationTest` (2) — lógica de cortes
- **Síntoma:** la solicitud no queda asociada al corte esperado; validación de solapamiento.
- **Naturaleza:** lógica de asignación de cortes según la fecha de asignación del técnico (negocio sensible).
- **Motivo de no abordar:** mismo criterio que el parser — riesgo alto en lógica de producción. Tras arreglar `LEAST` estos tests ya ejecutan; lo que resta son aserciones de negocio que deben validarse contra el comportamiento esperado real, no ajustarse a ciegas.

### 4. Puntuales: `TaskSubtaskReorderTest` (1), `CreateFastServiceRequestCommandTest` (1)
- **Síntoma:** redirect 302 inesperado / aserción de corte.
- **Naturaleza:** **setup del test incompleto** — no montan un workspace válido (company + contract + técnico accesible), por lo que el middleware de workspace purga el contexto y redirige.
- **Motivo de no abordar:** son arreglos test-por-test (montar bien los datos en cada uno), laboriosos y de bajo valor. La app se comporta correctamente; es el andamiaje del test el que está incompleto.

## Criterio aplicado

1. Priorizar arreglos de **raíz** sobre parches locales.
2. Preferir cambios que **también mejoren producción** (portabilidad, bug del middleware).
3. **No tocar lógica de negocio** (parser, cortes) sin investigación previa y
   confirmación de que hay un defecto real, para no romper comportamiento correcto.
4. Cada avance verificado se consolidó en un **commit independiente** (punto de
   respaldo), con la suite corrida para confirmar que no había regresiones.

## Cómo retomar

- Para EvidenceOrganizer: revisar `EvidenceOrganizerService` y cómo construye/crea
  rutas; considerar normalizar separadores y el directorio base en el entorno de test.
- Para parser y cortes: primero determinar si el fallo es un **bug real** o
  **fragilidad del test**; si es lo segundo, ajustar los datos/expectativas del test;
  si es lo primero, corregir la lógica con cobertura adicional.
- Para los puntuales: replicar el patrón de setup de los tests de cortes que sí
  pasan (crear company, contract activo, técnico y sesión de workspace válida).
