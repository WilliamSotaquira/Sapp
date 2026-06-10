# Requirements Document

## Introduction

Este documento define los requisitos para el sistema de organización de archivos de evidencia en carpetas estructuradas por número de corte. Los archivos de evidencia (descargas de correo, capturas de pantalla, enlaces) deben ser reubicados desde su ubicación actual hacia una estructura de carpetas organizada por corte, garantizando integridad de datos mediante respaldos temporales y sin afectar la base de datos existente.

## Glossary

- **Evidence_Organizer**: Servicio responsable de mover y organizar archivos de evidencia hacia la estructura de carpetas por corte.
- **Cut_Folder**: Carpeta física en el sistema de archivos que representa un corte específico y almacena las evidencias asociadas.
- **Cut**: Período de corte con fecha de inicio y fin, asociado a un contrato, que agrupa solicitudes de servicio y sus evidencias.
- **Evidence_File**: Archivo físico que sirve como evidencia de una solicitud de servicio (imagen, PDF, documento, captura de pantalla).
- **Source_Location**: Ubicación original del archivo antes de ser organizado (carpeta de descargas, carpeta de capturas, etc.).
- **Backup_Copy**: Copia temporal del archivo creada antes de mover el original, utilizada para garantizar integridad en la transferencia.
- **Date_Suggestion_Engine**: Componente que calcula y sugiere fechas/horas para la creación de nuevos cortes evitando solapamientos.
- **Base_Storage_Path**: Ruta padre configurable donde se almacenan todas las carpetas de corte con evidencias organizadas.
- **System_Settings**: Módulo de configuración del sistema donde se definen parámetros globales como la ruta base de almacenamiento.

## Requirements

### Requirement 1: Configurar carpeta base para almacenamiento de evidencias

**User Story:** Como administrador del sistema, quiero poder configurar la carpeta padre donde se almacenarán las evidencias organizadas, para controlar la ubicación de almacenamiento según los recursos disponibles del servidor.

#### Acceptance Criteria

1. THE System_Settings SHALL provide a text input field to define the base storage path (Base_Storage_Path) for organized evidence files, accepting absolute paths with a maximum length of 255 characters.
2. IF the Base_Storage_Path is not configured or is empty, THEN THE Evidence_Organizer SHALL use the default path `storage/app/public/evidences/cortes` as the base directory.
3. WHEN the administrator submits a new Base_Storage_Path value, THE System_Settings SHALL validate that the path contains only valid filesystem characters (alphanumeric, hyphens, underscores, forward slashes, and backslashes), that the specified directory exists, and that the application has write permissions on it.
4. IF the specified Base_Storage_Path does not exist or is not writable by the application, THEN THE System_Settings SHALL display an error message indicating the specific reason (directory not found or insufficient permissions) and reject the configuration change without modifying the stored value.
5. THE System_Settings SHALL persist the Base_Storage_Path value in the application database so it survives application restarts and redeployments.
6. IF the administrator is not authenticated with an administrator role, THEN THE System_Settings SHALL deny access to the Base_Storage_Path configuration option.
7. IF the persistence operation fails when saving the Base_Storage_Path, THEN THE System_Settings SHALL display an error message indicating the configuration could not be saved and retain the previous stored value.

### Requirement 2: Crear estructura de carpetas por corte con ruta personalizable

**User Story:** Como usuario del sistema, quiero que al crear un corte pueda confirmar o modificar la ruta donde se creará la carpeta del corte, para tener control sobre la ubicación final de las evidencias.

#### Acceptance Criteria

1. WHEN the user creates a new Cut, THE Evidence_Organizer SHALL display a suggested Cut_Folder path based on the Base_Storage_Path and the naming pattern `{Base_Storage_Path}/corte-{cut_id}-{start_date}`, where `start_date` is formatted as `YYYY-MM-DD`.
2. WHEN the create cut form is displayed, THE Evidence_Organizer SHALL allow the user to confirm or modify the suggested Cut_Folder folder name (the last segment of the path), while the parent path remains the configured Base_Storage_Path.
3. WHEN the user confirms the Cut_Folder path, THE Evidence_Organizer SHALL create the physical directory and store the full absolute path in the Cut database record.
4. IF the user specifies a custom folder name, THEN THE Evidence_Organizer SHALL validate that the name contains only alphanumeric characters, hyphens, and underscores, is between 1 and 128 characters long, and that no other Cut_Folder with the same name exists within the Base_Storage_Path.
5. IF the Cut_Folder already exists at the confirmed path, THEN THE Evidence_Organizer SHALL reuse the existing folder without overwriting or deleting its contents and proceed to register the path in the Cut record.
6. IF the directory creation fails due to insufficient permissions or disk errors, THEN THE Evidence_Organizer SHALL display an error message indicating the folder could not be created and SHALL NOT save the Cut record.
7. WHEN the Cut_Folder is created, THE Evidence_Organizer SHALL store the full absolute path in the Cut database record to allow independent path resolution regardless of future Base_Storage_Path changes.
8. WHEN an Evidence_File is organized into a Cut_Folder, THE Evidence_Organizer SHALL place it in a subdirectory named after the service request ticket number within that Cut_Folder, creating the subdirectory if it does not exist.

### Requirement 3: Mover archivos de evidencia a la carpeta del corte

**User Story:** Como usuario del sistema, quiero seleccionar archivos de evidencia y que el sistema los reubique en la carpeta del corte correspondiente, para mantener toda la información organizada.

#### Acceptance Criteria

1. WHEN the user selects between 1 and 50 Evidence_Files for organization, THE Evidence_Organizer SHALL move each file from its Source_Location to the Cut_Folder determined by the associated Cut identifier (e.g., `evidences/corte-{cut_id}/solicitud-{service_request_id}/`).
2. WHEN an Evidence_File is successfully moved, THE Evidence_Organizer SHALL update the file_path field in the service_request_evidences database record to reflect the new storage path within 5 seconds of the move completing.
3. WHEN an Evidence_File is moved, THE Evidence_Organizer SHALL preserve the original file name and extension without modification.
4. IF the user selects an Evidence_File that is an external URL (evidence_type ENLACE), THEN THE Evidence_Organizer SHALL register the URL in the Cut_Folder's evidence index record in the database without attempting a file system move operation.
5. WHEN multiple Evidence_Files are selected for organization, THE Evidence_Organizer SHALL process each file independently so that a failure in one file does not prevent the organization of the remaining files, and SHALL present a summary indicating the count of successfully moved files and the count of failed files.
6. IF an Evidence_File cannot be found at its Source_Location during the move operation, THEN THE Evidence_Organizer SHALL skip that file, record the failure, and continue processing the remaining files without altering the database record for the missing file.
7. IF a file with the same name already exists in the destination Cut_Folder, THEN THE Evidence_Organizer SHALL append a numeric suffix to the file name before the extension to avoid overwriting the existing file, and SHALL store the suffixed name in the database record.

### Requirement 4: Garantizar integridad mediante respaldos temporales

**User Story:** Como usuario del sistema, quiero que el proceso de mover archivos sea seguro y no destruya información existente, para proteger los datos de evidencia.

#### Acceptance Criteria

1. WHEN an Evidence_File is about to be moved, THE Evidence_Organizer SHALL create a Backup_Copy in a temporary directory within the Base_Storage_Path (named `_backups`) before initiating the transfer.
2. WHEN the Evidence_File has been successfully copied to the Cut_Folder, THE Evidence_Organizer SHALL verify that the destination file size in bytes matches the source file size in bytes.
3. WHEN the verification confirms the file was transferred correctly, THE Evidence_Organizer SHALL delete the Backup_Copy and remove the file from the Source_Location.
4. IF the verification fails (size mismatch or file not found at destination), THEN THE Evidence_Organizer SHALL restore the original file from the Backup_Copy to the Source_Location and display an error message to the user indicating which file failed and the reason (size mismatch or missing file).
5. IF the move operation fails at any point, THEN THE Evidence_Organizer SHALL leave the database records unchanged for that specific file.
6. THE Evidence_Organizer SHALL use database transactions to ensure that file_path updates are only committed after successful file transfer verification.
7. IF the Source_Location file does not exist or is not readable at the time of the move operation, THEN THE Evidence_Organizer SHALL skip that file, leave its database record unchanged, and display an error message to the user indicating the file was not accessible.
8. WHEN a Backup_Copy has remained in the temporary directory for more than 24 hours without being cleaned up by a successful or failed operation, THE Evidence_Organizer SHALL automatically delete the orphaned Backup_Copy to prevent unbounded disk usage.

### Requirement 5: Sugerir fechas para creación de nuevos cortes

**User Story:** Como usuario del sistema, quiero que al crear un nuevo corte el sistema me sugiera la fecha y hora adecuadas, para evitar solapamientos entre cortes del mismo contrato.

#### Acceptance Criteria

1. WHEN the user opens the create cut form, THE Date_Suggestion_Engine SHALL calculate and display a suggested start_date based on the end_date of the most recent cut for the active contract.
2. WHEN the most recent cut exists for the active contract, THE Date_Suggestion_Engine SHALL suggest a start_date equal to the end_date of the previous cut plus one calendar day at 00:00 hours.
3. IF no previous cut exists for the active contract, THEN THE Date_Suggestion_Engine SHALL suggest the current date and time (truncated to the current minute) as the start_date.
4. THE Date_Suggestion_Engine SHALL display the suggested start_date and end_date in the format YYYY-MM-DD HH:mm, where end_date defaults to the last day of the calendar month containing the suggested start_date at 23:59.
5. WHEN the user modifies the suggested dates, THE Date_Suggestion_Engine SHALL validate within 1 second that the new date range does not overlap with any existing cut for the same contract.
6. IF the user enters a date range that overlaps with an existing cut, THEN THE Date_Suggestion_Engine SHALL display a warning message indicating the conflicting cut name and its date range, and SHALL disable the form submission button until the overlap is resolved.
7. IF no active contract exists for the current workspace, THEN THE Date_Suggestion_Engine SHALL display a message indicating that a cut cannot be created without an active contract, and SHALL not display the date suggestion fields.

### Requirement 6: Registrar fecha y hora de creación en los cortes

**User Story:** Como usuario del sistema, quiero que cada corte almacene la fecha y hora exacta de creación, para tener trazabilidad completa del momento en que fue generado.

#### Acceptance Criteria

1. WHEN a new Cut is created, THE Cut SHALL store the creation timestamp with date and time precision (year, month, day, hour, and minute) in the created_at field, set automatically by the system at the moment of persistence.
2. WHEN displaying a Cut's information in any view (list, detail, or report), THE Cut SHALL render the created_at value in the format "DD/MM/YYYY HH:mm" using the server's configured timezone.
3. WHEN listing cuts, THE Cut list SHALL display the created_at timestamp in a dedicated column positioned after the period range columns (start_date and end_date), so the user can differentiate between the period the cut covers and the moment it was generated.
4. IF the created_at value is null or missing for a Cut record, THEN THE System SHALL display the text "Sin fecha de creación" in place of the formatted timestamp.

### Requirement 7: Proteger la base de datos durante las operaciones de organización

**User Story:** Como usuario del sistema, quiero que las operaciones de organización de archivos no afecten ni destruyan datos en la base de datos, para mantener la integridad del sistema.

#### Acceptance Criteria

1. THE Evidence_Organizer SHALL wrap all database modifications related to file organization in a database transaction.
2. IF any database operation fails during file organization, THEN THE Evidence_Organizer SHALL rollback the entire transaction, restore any already-moved files to their original filesystem location, and leave all records in their original state.
3. THE Evidence_Organizer SHALL validate that the service_request_evidences record exists, has not been soft-deleted, and that its associated file is present on the configured storage disk before attempting any file move operation.
4. WHEN updating file_path records, THE Evidence_Organizer SHALL acquire a row-level lock (SELECT FOR UPDATE) on the target service_request_evidences record to prevent concurrent modification conflicts.
5. IF a row-level lock cannot be acquired within 5 seconds, THEN THE Evidence_Organizer SHALL abort the operation without modifying the record and return an error indicating a concurrent modification conflict.
6. THE Evidence_Organizer SHALL log each file organization operation (source path, destination path, timestamp, user, result) to a persistent audit log that is retained for a minimum of 90 days.

### Requirement 8: Organizar evidencias en un corte existente sin crear uno nuevo

**User Story:** Como usuario del sistema, quiero poder agregar evidencias a un corte existente sin necesidad de crear uno nuevo, para acumular las evidencias del período en curso.

#### Acceptance Criteria

1. WHEN the user selects a Cut that already has a Cut_Folder, THE Evidence_Organizer SHALL allow adding new Evidence_Files to the existing folder structure.
2. WHEN adding files to an existing Cut_Folder, THE Evidence_Organizer SHALL place each file in the subdirectory corresponding to its service request ticket number.
3. IF a file with the same name already exists in the target subdirectory, THEN THE Evidence_Organizer SHALL append a numeric suffix to the new file name to avoid overwriting.
4. THE Evidence_Organizer SHALL update the evidence count displayed in the cut detail view after each successful file organization.
