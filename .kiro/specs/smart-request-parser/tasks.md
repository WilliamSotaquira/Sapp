# Implementation Plan: Smart Request Parser

## Overview

Plan de implementación para el Parser Inteligente de Solicitudes. Se sigue un enfoque incremental: primero los value objects e interfaces base, luego los extractores individuales en orden del pipeline, después la orquestación del pipeline completo, y finalmente la integración con el servicio existente y la UI. Cada extractor incluye sus tests unitarios y property-based tests como sub-tareas.

## Tasks

- [x] 1. Configurar estructura del proyecto e interfaces base
  - [x] 1.1 Crear value objects (ParsedResult, ExtractionResult, ParsingContext)
    - Crear `app/Services/SmartParser/ValueObjects/ParsedResult.php` con todas las propiedades readonly y el método `toPayload()`
    - Crear `app/Services/SmartParser/ValueObjects/ExtractionResult.php` con factory method `empty()`
    - Crear `app/Services/SmartParser/ValueObjects/ParsingContext.php` con propiedades mutables del contexto compartido
    - _Requirements: 9.4, 12.4_

  - [x] 1.2 Crear interfaz FieldExtractorInterface y estructura de directorios
    - Crear `app/Services/SmartParser/Contracts/FieldExtractorInterface.php` con método `extract(ParsingContext): ExtractionResult`
    - Crear directorios: `app/Services/SmartParser/Extractors/`, `app/Services/SmartParser/Resolvers/`
    - _Requirements: 12.4_

  - [ ]* 1.3 Escribir tests unitarios para value objects
    - Verificar que `ParsedResult::toPayload()` genera la estructura correcta del array
    - Verificar que `ExtractionResult::empty()` retorna confianza 0 y extracted=false
    - _Requirements: 12.4_

- [x] 2. Implementar TextNormalizer
  - [x] 2.1 Crear TextNormalizer con métodos normalize(), removeQuoteMarkers(), deduplicateBlocks()
    - Implementar eliminación de caracteres de control Unicode
    - Implementar colapso de múltiples saltos de línea a máximo 2
    - Implementar reemplazo de tabulaciones por espacios simples
    - Implementar eliminación de líneas con prefijo ">" (citado)
    - Implementar deduplicación de bloques de texto idénticos
    - Crear en `app/Services/SmartParser/TextNormalizer.php`
    - _Requirements: 9.8, 9.10, 9.11_

  - [ ]* 2.2 Escribir property test para TextNormalizer
    - **Property 12: Normalización de texto elimina ruido preservando contenido**
    - **Validates: Requirements 9.8, 9.10, 9.11**

  - [ ]* 2.3 Escribir tests unitarios para TextNormalizer
    - Casos: texto con tabulaciones, saltos de línea excesivos, caracteres de control, bloques duplicados, marcadores de citado
    - _Requirements: 9.8, 9.10, 9.11_

- [x] 3. Implementar StructuredFormatDetector
  - [x] 3.1 Crear StructuredFormatDetector con método isStructuredFormat()
    - Implementar detección del formato predefinido existente verificando la presencia de campos en orden específico
    - Crear en `app/Services/SmartParser/StructuredFormatDetector.php`
    - _Requirements: 9.3, 12.1, 12.2_

  - [ ]* 3.2 Escribir property test para StructuredFormatDetector
    - **Property 13: Compatibilidad con formato estructurado existente**
    - **Validates: Requirements 12.1, 12.2, 12.3, 12.5**

  - [ ]* 3.3 Escribir tests unitarios para StructuredFormatDetector
    - Casos: texto en formato estructurado válido, texto libre, texto parcialmente estructurado
    - _Requirements: 12.1, 12.2_

- [x] 4. Checkpoint - Verificar componentes base
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Implementar ChannelDetector
  - [x] 5.1 Crear ChannelDetector que implementa FieldExtractorInterface
    - Implementar detección de encabezados de correo (De/From, Para/To, Asunto/Subject, Fecha/Date)
    - Implementar detección de patrones WhatsApp ([DD/MM/AAAA, HH:MM] Contacto: y DD/MM/AAAA HH:MM - Contacto:)
    - Implementar lógica de conteo: 2+ encabezados = email, patrones WhatsApp = whatsapp
    - Implementar resolución de conflictos por mayor número de coincidencias
    - Implementar valor por defecto "email_corporativo" cuando no hay coincidencias
    - Crear en `app/Services/SmartParser/Extractors/ChannelDetector.php`
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

  - [ ]* 5.2 Escribir property test para ChannelDetector
    - **Property 1: Detección de canal por conteo de patrones**
    - **Validates: Requirements 1.1, 1.2, 1.3, 1.4**

  - [ ]* 5.3 Escribir tests unitarios para ChannelDetector
    - Casos: correo con todos los encabezados, correo con 2 encabezados, WhatsApp formato corchetes, WhatsApp formato guión, texto mixto, texto sin patrones
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [ ] 6. Implementar RequesterExtractor y RequesterResolver
  - [x] 6.1 Crear RequesterExtractor que implementa FieldExtractorInterface
    - Implementar extracción del nombre desde campo "De:" en correos
    - Implementar extracción del nombre desde prefijo de mensaje WhatsApp
    - Implementar extracción heurística por bloques cuando no hay formato reconocido
    - Crear en `app/Services/SmartParser/Extractors/RequesterExtractor.php`
    - _Requirements: 2.1, 2.2, 2.6, 2.7_

  - [x] 6.2 Crear RequesterResolver con método resolve()
    - Implementar búsqueda por email exacto (case-insensitive)
    - Implementar búsqueda por nombre normalizado (sin tildes, sin mayúsculas, espacios colapsados)
    - Implementar marcado como pendiente cuando no hay coincidencia
    - Crear en `app/Services/SmartParser/Resolvers/RequesterResolver.php`
    - _Requirements: 2.3, 2.4, 2.5_

  - [ ]* 6.3 Escribir property test para RequesterResolver
    - **Property 2: Resolución de solicitante por coincidencia normalizada**
    - **Validates: Requirements 2.3, 2.4, 2.5**

  - [ ]* 6.4 Escribir tests unitarios para RequesterExtractor y RequesterResolver
    - Casos: correo con "De: Nombre <email>", WhatsApp con contacto, texto sin remitente, resolución con coincidencia exacta, resolución con nombre normalizado, sin coincidencia
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7_

- [ ] 7. Implementar TitleDescriptionExtractor
  - [x] 7.1 Crear TitleDescriptionExtractor que implementa FieldExtractorInterface
    - Implementar extracción de título desde "Asunto:"/"Subject:" con truncamiento a 255 chars en último espacio
    - Implementar generación de título desde primera oración con 10+ caracteres cuando no hay asunto
    - Implementar extracción de descripción del cuerpo del mensaje (máx 5000 chars)
    - Implementar exclusión de firmas (detectar "--", "Regards,", "Saludos,", "Atentamente,", etc.)
    - Implementar exclusión de mensajes anteriores del hilo (detectar "El ... escribió:", "On ... wrote:", "From:", "De:")
    - Implementar manejo de correos reenviados (extraer del mensaje original reenviado)
    - Implementar eliminación de líneas citadas (prefijo ">")
    - Crear en `app/Services/SmartParser/Extractors/TitleDescriptionExtractor.php`
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.7, 3.8, 3.9, 3.10_

  - [ ]* 7.2 Escribir property test para extracción de título
    - **Property 3: Extracción de título con truncamiento correcto**
    - **Validates: Requirements 3.1, 3.2**

  - [ ]* 7.3 Escribir property test para extracción de descripción
    - **Property 4: Extracción de descripción limpia de hilos y firmas**
    - **Validates: Requirements 3.3, 3.4, 3.5, 3.7, 3.8, 3.9, 3.10**

  - [ ]* 7.4 Escribir tests unitarios para TitleDescriptionExtractor
    - Casos: correo con asunto, correo sin asunto, correo con hilo de respuestas, correo reenviado, correo con firma, texto con líneas citadas, texto vacío después de limpieza
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10_

- [ ] 8. Implementar SubServiceClassifier
  - [x] 8.1 Crear SubServiceClassifier que implementa FieldExtractorInterface
    - Implementar cálculo de puntuación de similitud textual contra subservicios activos del contrato
    - Implementar comparación contra nombre, código, servicio padre y familia del subservicio
    - Implementar umbral mínimo del 55% para asignación automática
    - Implementar resolución del servicio padre, familia y SLA activo asociados
    - Crear en `app/Services/SmartParser/Extractors/SubServiceClassifier.php`
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [ ]* 8.2 Escribir property test para SubServiceClassifier
    - **Property 5: Clasificación de subservicio por umbral de similitud**
    - **Validates: Requirements 4.1, 4.2, 4.3, 4.4**

  - [ ]* 8.3 Escribir tests unitarios para SubServiceClassifier
    - Casos: coincidencia exacta por nombre, coincidencia parcial sobre umbral, coincidencia bajo umbral, sin coincidencia, subservicio sin SLA activo
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 9. Implementar DateExtractor
  - [x] 9.1 Crear DateExtractor que implementa FieldExtractorInterface
    - Implementar detección de fecha en encabezado "Fecha:"/"Date:" con prioridad
    - Implementar detección de formato español textual (dd de mes de yyyy)
    - Implementar detección de formato numérico (dd/mm/yyyy, dd-mm-yyyy)
    - Implementar selección de fecha de creación: encabezado > primera fecha del cuerpo > fecha actual
    - Implementar detección de frases de plazo ("fecha límite", "antes del", "vence el", "deadline", etc.)
    - Implementar extracción de due_date cuando hay frase de plazo seguida/precedida de fecha
    - Crear en `app/Services/SmartParser/Extractors/DateExtractor.php`
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

  - [ ]* 9.2 Escribir property test para extracción de fecha de creación
    - **Property 6: Extracción de fechas con prioridad de encabezado**
    - **Validates: Requirements 5.1, 5.2, 5.3, 5.6**

  - [ ]* 9.3 Escribir property test para extracción de due_date
    - **Property 7: Extracción de due_date por frases de plazo**
    - **Validates: Requirements 5.4, 5.5**

  - [ ]* 9.4 Escribir tests unitarios para DateExtractor
    - Casos: fecha en encabezado, fecha textual en cuerpo, fecha numérica, múltiples fechas, frase de plazo con fecha, sin fechas, sin frases de plazo
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

- [x] 10. Checkpoint - Verificar extractores principales
  - Ensure all tests pass, ask the user if questions arise.

- [x] 11. Implementar UrlExtractor
  - [x] 11.1 Crear UrlExtractor que implementa FieldExtractorInterface
    - Implementar detección de URLs con regex para http/https
    - Implementar eliminación de duplicados
    - Implementar límite máximo de 8 URLs
    - Crear en `app/Services/SmartParser/Extractors/UrlExtractor.php`
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

  - [ ]* 11.2 Escribir property test para UrlExtractor
    - **Property 8: Extracción de URLs únicas con límite máximo**
    - **Validates: Requirements 6.1, 6.2, 6.3**

  - [ ]* 11.3 Escribir tests unitarios para UrlExtractor
    - Casos: texto con múltiples URLs, URLs duplicadas, más de 8 URLs, sin URLs, URLs con parámetros
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [x] 12. Implementar CriticalityDetector
  - [x] 12.1 Crear CriticalityDetector que implementa FieldExtractorInterface
    - Implementar detección de keywords por nivel (CRITICA, URGENTE, ALTA, BAJA) case-insensitive
    - Implementar selección del nivel más alto cuando hay múltiples indicadores
    - Implementar valor por defecto MEDIA cuando no hay indicadores
    - Crear en `app/Services/SmartParser/Extractors/CriticalityDetector.php`
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7_

  - [ ]* 12.2 Escribir property test para CriticalityDetector
    - **Property 9: Detección de criticidad por keyword más alto**
    - **Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7**

  - [ ]* 12.3 Escribir tests unitarios para CriticalityDetector
    - Casos: texto con keyword CRITICA, URGENTE, ALTA, BAJA, múltiples niveles, sin keywords, keywords en mayúsculas/minúsculas
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7_

- [x] 13. Implementar TaskGenerator
  - [x] 13.1 Crear TaskGenerator que implementa FieldExtractorInterface
    - Implementar detección de listas de acciones (viñetas *, -, •, numeración 1., 2., a), b))
    - Implementar generación de subtareas por cada elemento válido (3-255 caracteres, máx 20)
    - Implementar detección de duraciones explícitas ("30 minutos", "2 horas", "15 min", "1h") con clamp 5-480 min
    - Implementar asignación de 25 minutos por defecto cuando no hay duración explícita
    - Implementar cálculo de estimated_hours como suma de estimated_minutes / 60
    - Implementar generación de tarea única cuando no hay lista de acciones
    - Crear en `app/Services/SmartParser/Extractors/TaskGenerator.php`
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8_

  - [ ]* 13.2 Escribir property test para generación de subtareas
    - **Property 10: Generación de subtareas desde listas con validación de longitud**
    - **Validates: Requirements 8.1, 8.3, 8.6, 8.8**

  - [ ]* 13.3 Escribir property test para cálculo de estimated_hours
    - **Property 11: Cálculo de estimated_hours como suma de subtareas**
    - **Validates: Requirements 8.7**

  - [ ]* 13.4 Escribir tests unitarios para TaskGenerator
    - Casos: lista con viñetas, lista numerada, elementos con duración, elementos muy cortos/largos, más de 20 elementos, sin lista (tarea única), cálculo de estimated_hours
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8_

- [x] 14. Checkpoint - Verificar todos los extractores
  - Ensure all tests pass, ask the user if questions arise.

- [x] 15. Implementar SmartParserPipeline
  - [x] 15.1 Crear SmartParserPipeline con método parse()
    - Inyectar todos los extractores vía constructor
    - Implementar ejecución secuencial: normalizar → detectar canal → extraer solicitante → título/descripción → subservicio → fechas → URLs → criticidad → tareas
    - Implementar propagación del contexto entre extractores
    - Implementar recolección de niveles de confianza por campo
    - Implementar tolerancia a fallos parciales (continuar si un campo falla)
    - Crear en `app/Services/SmartParser/SmartParserPipeline.php`
    - _Requirements: 9.1, 9.4, 11.2, 11.3_

  - [ ]* 15.2 Escribir property test para degradación graceful del pipeline
    - **Property 14: Degradación graceful cuando campos no son extraíbles**
    - **Validates: Requirements 11.2, 11.3**

  - [ ]* 15.3 Escribir tests unitarios para SmartParserPipeline
    - Casos: texto completo con todos los campos, texto con campos faltantes, texto que genera error parcial
    - _Requirements: 9.1, 9.4, 11.2, 11.3_

- [x] 16. Integrar con ServiceRequestPlainTextImportService
  - [x] 16.1 Modificar ServiceRequestPlainTextImportService para usar el pipeline inteligente
    - Inyectar `SmartParserPipeline` y `StructuredFormatDetector` en el constructor
    - Modificar `parseToFormData()` para: detectar formato → si estructurado usar algoritmo original → si libre usar SmartParserPipeline
    - Implementar validaciones de entrada (texto < 20 chars, > 50000 chars, sin contrato activo)
    - Implementar conversión de `ParsedResult` al formato de salida compatible (payload + meta)
    - Mantener la firma pública sin cambios: `parseToFormData(string $plainText, int $companyId, ?int $requestedBy = null): array`
    - _Requirements: 11.1, 11.4, 11.5, 12.2, 12.3, 12.4, 12.5_

  - [ ]* 16.2 Escribir tests de integración para compatibilidad con formato estructurado
    - Verificar que textos en formato estructurado producen salida idéntica al servicio original
    - Verificar que excepciones de validación se mantienen iguales
    - _Requirements: 12.2, 12.5_

  - [ ]* 16.3 Escribir tests de integración end-to-end del pipeline completo
    - Probar con correos electrónicos reales anonimizados
    - Probar con mensajes de WhatsApp en diferentes formatos
    - Probar con texto libre sin formato reconocido
    - _Requirements: 9.1, 9.2, 9.4_

- [x] 17. Checkpoint - Verificar integración del servicio
  - Ensure all tests pass, ask the user if questions arise.

- [x] 18. Implementar componente UI para importación de texto
  - [x] 18.1 Crear componente Livewire/Blade para el área de texto de importación
    - Agregar textarea en el formulario de creación de solicitudes con mínimo 20 caracteres para habilitar acción
    - Implementar botón "Interpretar" que envía el texto al endpoint existente
    - Implementar indicador de procesamiento (spinner/loading) durante la interpretación
    - Implementar pre-llenado del formulario con los datos extraídos
    - Implementar indicación visual de campos pre-llenados por el parser vs campos vacíos
    - Implementar manejo de errores con mensaje específico (texto corto, sin contrato, timeout)
    - Implementar aviso visible cuando el solicitante es pendiente de creación
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6_

  - [ ]* 18.2 Escribir tests unitarios para el componente UI
    - Verificar que el textarea valida mínimo 20 caracteres
    - Verificar que el indicador de procesamiento se muestra/oculta correctamente
    - Verificar que los campos se pre-llenan correctamente
    - _Requirements: 10.1, 10.2, 10.4_

- [x] 19. Implementar manejo de errores y logging
  - [x] 19.1 Implementar manejo de timeout y errores inesperados
    - Implementar control de tiempo máximo de 30 segundos para el pipeline
    - Implementar logging de errores inesperados (tipo excepción, workspace_id, longitud texto)
    - Implementar mensaje genérico al usuario sin detalles técnicos internos
    - _Requirements: 11.6, 11.7_

  - [ ]* 19.2 Escribir tests para manejo de errores
    - Verificar timeout de 30 segundos
    - Verificar logging de excepciones inesperadas
    - Verificar mensajes de error al usuario
    - _Requirements: 11.6, 11.7_

- [x] 20. Registrar ServiceProvider y wiring final
  - [x] 20.1 Registrar bindings en el ServiceProvider de Laravel
    - Registrar `SmartParserPipeline` con sus dependencias en el contenedor IoC
    - Registrar `StructuredFormatDetector` y `TextNormalizer`
    - Verificar que la inyección de dependencias funciona correctamente en `ServiceRequestPlainTextImportService`
    - _Requirements: 12.4_

  - [ ]* 20.2 Escribir test de integración del controlador
    - Verificar endpoint `prefillFromPlainText` con texto libre
    - Verificar endpoint con texto estructurado
    - Verificar respuesta de error con texto inválido
    - _Requirements: 10.2, 12.4_

- [x] 21. Final checkpoint - Verificar sistema completo
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Las tareas marcadas con `*` son opcionales y pueden omitirse para un MVP más rápido
- Cada tarea referencia requisitos específicos para trazabilidad
- Los checkpoints aseguran validación incremental
- Los property tests validan propiedades universales de correctitud definidas en el diseño
- Los tests unitarios validan ejemplos específicos y edge cases
- Se usa Eris como librería de property-based testing para PHP
- El pipeline se implementa en orden secuencial para que cada extractor pueda usar el contexto enriquecido por los anteriores
- La compatibilidad con el formato estructurado existente es crítica: el detector de formato se ejecuta primero y delega al algoritmo original cuando corresponde

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2"] },
    { "id": 1, "tasks": ["1.3", "2.1", "3.1"] },
    { "id": 2, "tasks": ["2.2", "2.3", "3.2", "3.3"] },
    { "id": 3, "tasks": ["5.1", "11.1", "12.1"] },
    { "id": 4, "tasks": ["5.2", "5.3", "6.1", "11.2", "11.3", "12.2", "12.3"] },
    { "id": 5, "tasks": ["6.2", "6.3", "6.4", "7.1"] },
    { "id": 6, "tasks": ["7.2", "7.3", "7.4", "8.1", "9.1"] },
    { "id": 7, "tasks": ["8.2", "8.3", "9.2", "9.3", "9.4", "13.1"] },
    { "id": 8, "tasks": ["13.2", "13.3", "13.4"] },
    { "id": 9, "tasks": ["15.1"] },
    { "id": 10, "tasks": ["15.2", "15.3", "16.1"] },
    { "id": 11, "tasks": ["16.2", "16.3", "19.1"] },
    { "id": 12, "tasks": ["18.1", "19.2"] },
    { "id": 13, "tasks": ["18.2", "20.1"] },
    { "id": 14, "tasks": ["20.2"] }
  ]
}
```
