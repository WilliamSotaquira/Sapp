# Requirements Document

## Introduction

Esta funcionalidad agrega una validación adicional al botón "Resolver Solicitud" en la vista de detalle de una solicitud de servicio. El botón de resolver solo se muestra cuando al menos una subtarea está completada. Si ninguna subtarea está completada, se muestra un botón ancla que realiza scroll suave hasta la sección de "Tareas Asociadas", guiando al usuario a completar trabajo antes de resolver.

## Glossary

- **Service_Request_Detail_View**: Vista de detalle de una solicitud de servicio ubicada en la ruta `service-requests/{id}`
- **Resolve_Button**: Botón "Resolver Solicitud" que permite al usuario marcar una solicitud como resuelta
- **Workflow_Actions_Component**: Componente Blade `workflow-actions.blade.php` ubicado en el header de la vista de detalle que muestra acciones de flujo de trabajo
- **Tasks_Panel_Component**: Componente Blade `tasks-panel.blade.php` que muestra la sección de "Tareas Asociadas" en la vista de detalle
- **Subtask**: Unidad de trabajo individual dentro de una tarea, con campo booleano `is_completed` que indica si fue completada
- **Anchor_Button**: Botón de navegación interna que realiza scroll suave hasta una sección específica de la página
- **Tasks_Panel_Section**: Sección de "Tareas Asociadas" identificada por el atributo `id="tasks-panel-{serviceRequestId}"`
- **Subtask_Completion_Validation**: Verificación de que al menos una subtarea individual tenga `is_completed = true`, independientemente del estado de la tarea padre

## Requirements

### Requirement 1

**User Story:** Como técnico, quiero que el sistema valide que al menos una subtarea esté completada antes de permitirme resolver una solicitud, para evitar resolver solicitudes sin trabajo registrado.

#### Acceptance Criteria

1. WHILE la Service_Request_Detail_View muestra una solicitud en estado "EN_PROCESO", THE Workflow_Actions_Component SHALL evaluar si al menos una Subtask asociada a la solicitud tiene `is_completed = true` antes de mostrar el Resolve_Button.
2. WHILE la Service_Request_Detail_View muestra una solicitud en estado "EN_PROCESO", THE Tasks_Panel_Component SHALL evaluar si al menos una Subtask asociada a la solicitud tiene `is_completed = true` antes de mostrar el Resolve_Button.
3. WHEN al menos una Subtask asociada a la solicitud tiene `is_completed = true`, THE Workflow_Actions_Component SHALL mostrar el Resolve_Button habilitado con su comportamiento normal de abrir el modal de resolución.
4. WHEN al menos una Subtask asociada a la solicitud tiene `is_completed = true`, THE Tasks_Panel_Component SHALL mostrar el Resolve_Button habilitado con su comportamiento normal de abrir el modal de resolución.

### Requirement 2

**User Story:** Como técnico, quiero que cuando no haya subtareas completadas se me guíe hacia la sección de tareas, para saber qué debo completar antes de resolver la solicitud.

#### Acceptance Criteria

1. WHEN ninguna Subtask asociada a la solicitud tiene `is_completed = true`, THE Workflow_Actions_Component SHALL mostrar un Anchor_Button en lugar del Resolve_Button.
2. WHEN ninguna Subtask asociada a la solicitud tiene `is_completed = true`, THE Tasks_Panel_Component SHALL mostrar un Anchor_Button en lugar del Resolve_Button.
3. THE Anchor_Button SHALL mostrar un texto e icono que indique navegación hacia la sección de tareas asociadas.
4. WHEN el usuario hace clic en el Anchor_Button, THE Service_Request_Detail_View SHALL realizar un scroll suave (smooth scroll) hasta la Tasks_Panel_Section identificada por `id="tasks-panel-{serviceRequestId}"`.
5. THE Anchor_Button SHALL mantener el mismo estilo visual (clases CSS, dimensiones, bordes redondeados) que los demás botones de acción del componente donde se encuentra.

### Requirement 3

**User Story:** Como técnico, quiero que la validación de subtareas sea adicional a la validación de evidencia existente, para que ambas condiciones se cumplan antes de poder resolver.

#### Acceptance Criteria

1. THE Workflow_Actions_Component SHALL requerir que tanto la condición `canResolveByEvidence` como la Subtask_Completion_Validation se cumplan para mostrar el Resolve_Button.
2. THE Tasks_Panel_Component SHALL requerir que tanto la condición `canResolveByEvidence` como la Subtask_Completion_Validation se cumplan para mostrar el Resolve_Button.
3. IF la condición `canResolveByEvidence` es falsa y la Subtask_Completion_Validation es verdadera, THEN THE Workflow_Actions_Component SHALL mostrar el Resolve_Button deshabilitado con el mensaje existente sobre evidencia.
4. IF la condición `canResolveByEvidence` es verdadera y la Subtask_Completion_Validation es falsa, THEN THE Workflow_Actions_Component SHALL mostrar el Anchor_Button hacia la Tasks_Panel_Section.

### Requirement 4

**User Story:** Como técnico, quiero que la validación considere subtareas individuales sin importar el estado de la tarea padre, para tener flexibilidad en el flujo de trabajo.

#### Acceptance Criteria

1. THE Subtask_Completion_Validation SHALL verificar el campo `is_completed` de las subtareas individuales asociadas a las tareas de la solicitud.
2. THE Subtask_Completion_Validation SHALL considerar válida la condición cuando al menos una Subtask tiene `is_completed = true`, independientemente del campo `status` de la tarea padre (Task).
3. THE Subtask_Completion_Validation SHALL recorrer todas las tareas asociadas a la solicitud y sus respectivas subtareas para determinar si existe al menos una subtarea completada.
