# Requirements Document

## Introduction

Este documento define los requisitos para el **Parser Inteligente de Solicitudes**, una mejora al mecanismo existente de importación de texto plano (`ServiceRequestPlainTextImportService`) que actualmente solo funciona con una estructura predefinida. El objetivo es permitir al usuario copiar y pegar texto libre proveniente de correos electrónicos (corporativos u oficiales) y mensajes de WhatsApp, y que el sistema interprete automáticamente la información necesaria para generar una solicitud de servicio completa, incluyendo tareas y subtareas asociadas.

## Glossary

- **Parser_Inteligente**: Componente del sistema encargado de analizar texto no estructurado proveniente de diferentes canales (correo, WhatsApp) y extraer los datos necesarios para crear una solicitud de servicio.
- **Solicitud_de_Servicio**: Registro en el sistema que representa un requerimiento de trabajo, con campos como título, descripción, solicitante, subservicio, criticidad, canal de entrada, rutas web y tareas asociadas.
- **Texto_Fuente**: Texto copiado y pegado por el usuario desde un correo electrónico o mensaje de WhatsApp que contiene la información de una solicitud.
- **Canal_de_Entrada**: Medio por el cual se recibe la solicitud (email_corporativo, email_digital, whatsapp, telefono, reunion).
- **Subservicio**: Categoría específica del servicio solicitado, vinculada a un contrato activo.
- **Tarea**: Unidad de trabajo asociada a una solicitud de servicio, con título, descripción, tipo, prioridad y tiempo estimado.
- **Subtarea**: División más granular de una tarea, con título, notas, prioridad y tiempo estimado.
- **Solicitante**: Persona que origina la solicitud, identificada por nombre y opcionalmente correo electrónico.
- **Nivel_de_Confianza**: Indicador porcentual que refleja la certeza del Parser_Inteligente sobre la correctitud de cada campo extraído.
- **Formato_Estructurado**: Formato predefinido con campos en orden específico que el parser actual reconoce.
- **Formato_Libre**: Texto sin estructura predefinida, como el cuerpo de un correo o un mensaje de WhatsApp.
- **Hilo_de_Correo**: Secuencia de mensajes de correo electrónico encadenados por respuestas (Re:, RE:) o reenvíos (Fwd:, Fw:, Rv:, RV:), donde cada respuesta incluye el contenido de los mensajes anteriores.
- **Correo_Reenviado**: Mensaje de correo electrónico que ha sido reenviado por un intermediario, identificable por prefijos como "Fwd:", "Fw:", "Rv:", "RV:" en el asunto o por marcadores de reenvío en el cuerpo.
- **Contenido_Desordenado**: Texto pegado por el usuario que no sigue un orden lógico, pudiendo contener fragmentos duplicados, encabezados dispersos, marcadores de citado intercalados y mezcla de contenido de diferentes fuentes.

## Requirements

### Requisito 1: Detección del canal de entrada

**Historia de Usuario:** Como administrador de sitios web, quiero que el sistema detecte automáticamente si el texto pegado proviene de un correo electrónico o de WhatsApp, para que el canal de entrada se asigne correctamente sin intervención manual.

#### Criterios de Aceptación

1. WHEN el Texto_Fuente contiene al menos 2 de los siguientes encabezados de correo electrónico (De/From, Para/To, Asunto/Subject, Fecha/Date), THE Parser_Inteligente SHALL clasificar el Canal_de_Entrada como "email_corporativo"
2. WHEN el Texto_Fuente contiene líneas con el formato de mensaje de WhatsApp (marca de tiempo seguida de nombre de contacto y separador, por ejemplo "[DD/MM/AAAA, HH:MM] Contacto:" o "DD/MM/AAAA HH:MM - Contacto:"), THE Parser_Inteligente SHALL clasificar el Canal_de_Entrada como "whatsapp"
3. IF el Texto_Fuente coincide con patrones de ambos canales simultáneamente, THEN THE Parser_Inteligente SHALL clasificar el Canal_de_Entrada según el patrón con mayor número de coincidencias detectadas
4. IF el Parser_Inteligente no puede determinar el canal de origen porque el Texto_Fuente no coincide con ningún patrón reconocido o el Texto_Fuente está vacío, THEN THE Parser_Inteligente SHALL asignar "email_corporativo" como Canal_de_Entrada por defecto
5. WHEN el usuario modifica manualmente el Canal_de_Entrada después de la clasificación automática, THE Parser_Inteligente SHALL preservar la selección del usuario y no volver a ejecutar la detección automática sobre ese registro

### Requisito 2: Extracción del solicitante

**Historia de Usuario:** Como administrador de sitios web, quiero que el sistema identifique automáticamente quién envía la solicitud a partir del texto pegado, para no tener que buscar manualmente al solicitante en el sistema.

#### Criterios de Aceptación

1. WHEN el Texto_Fuente contiene encabezados de correo electrónico (campos "De:", "Para:", "Fecha:" o marcadores de reenvío "Re:", "Rv:", "Fw:"), THE Parser_Inteligente SHALL extraer el nombre del remitente desde el campo "De" o, en su ausencia, desde la primera línea no vacía posterior al asunto del correo
2. WHEN el Texto_Fuente contiene el formato de mensaje de WhatsApp (patrón "[fecha, hora] Nombre:" o "Nombre - fecha, hora:"), THE Parser_Inteligente SHALL extraer el nombre del contacto desde el prefijo anterior al contenido del mensaje
3. WHEN el Parser_Inteligente identifica un nombre de solicitante, THE Parser_Inteligente SHALL buscar coincidencias en la base de datos de solicitantes del espacio de trabajo activo comparando por email exacto (sin distinción de mayúsculas) y por nombre normalizado (sin tildes, sin distinción de mayúsculas, espacios colapsados), y seleccionar la primera coincidencia encontrada
4. WHEN el solicitante identificado existe en la base de datos, THE Parser_Inteligente SHALL asignar automáticamente el solicitante_id correspondiente al formulario de creación de solicitud
5. WHEN el solicitante identificado no existe en la base de datos, THE Parser_Inteligente SHALL dejar el campo solicitante_id vacío, marcar el solicitante como pendiente de creación, y mostrar el nombre detectado (máximo 255 caracteres) en el formulario para que el usuario confirme o corrija
6. IF el Parser_Inteligente no puede identificar un nombre de solicitante en el texto (ningún patrón de correo ni de WhatsApp produce un nombre no vacío tras eliminar espacios), THEN THE Parser_Inteligente SHALL dejar el campo de solicitante vacío para selección manual sin mostrar mensaje de error
7. IF el Texto_Fuente no coincide con ningún formato reconocido (ni correo electrónico ni WhatsApp), THEN THE Parser_Inteligente SHALL aplicar extracción heurística por bloques de texto y, si no identifica un nombre, dejar el campo de solicitante vacío para selección manual

### Requisito 3: Extracción del título y descripción

**Historia de Usuario:** Como administrador de sitios web, quiero que el sistema genere automáticamente un título conciso y una descripción detallada a partir del texto pegado, para agilizar la creación de la solicitud.

#### Criterios de Aceptación

1. WHEN el Texto_Fuente contiene una línea de asunto de correo electrónico (línea que comienza con "Subject:" o "Asunto:"), THE Parser_Inteligente SHALL utilizar el contenido del asunto como título de la solicitud, truncado a un máximo de 255 caracteres cortando en el último espacio antes del límite
2. WHEN el Texto_Fuente no contiene una línea de asunto identificable (ninguna línea comienza con "Subject:" o "Asunto:"), THE Parser_Inteligente SHALL generar un título a partir de la primera oración del texto que contenga 10 o más caracteres excluyendo líneas de saludo y líneas en blanco, truncado a un máximo de 255 caracteres cortando en el último espacio antes del límite
3. THE Parser_Inteligente SHALL extraer el cuerpo del mensaje como descripción de la solicitud, con una longitud máxima de 5000 caracteres, excluyendo encabezados de correo, firmas y mensajes anteriores del hilo
4. WHEN el Texto_Fuente contiene hilos de correo (respuestas encadenadas separadas por marcadores como "---", "From:", "De:", "El ... escribió:", "On ... wrote:"), THE Parser_Inteligente SHALL utilizar únicamente el primer bloque de mensaje antes del primer marcador de respuesta anterior para la descripción
5. WHEN el Texto_Fuente contiene firmas de correo electrónico (texto posterior a delimitadores comunes como "--", "Regards,", "Saludos,", "Atentamente,"), THE Parser_Inteligente SHALL excluir las firmas de la descripción extraída
6. IF el Texto_Fuente está vacío o no contiene texto procesable después de excluir firmas y encabezados, THEN THE Parser_Inteligente SHALL indicar un error informando que no se pudo extraer título ni descripción del texto proporcionado
7. WHEN el Texto_Fuente contiene un correo reenviado (identificado por prefijos "Fwd:", "Fw:", "Rv:", "RV:" en el asunto o por marcadores "---------- Forwarded message ----------", "---------- Mensaje reenviado ----------", "Inicio del mensaje reenviado", "Begin forwarded message"), THE Parser_Inteligente SHALL extraer el título y la descripción del mensaje reenviado original (el contenido posterior al marcador de reenvío) en lugar del mensaje contenedor
8. WHEN el Texto_Fuente contiene un hilo de respuestas con múltiples niveles de anidación (múltiples bloques separados por marcadores "Re:", "RE:", "De:", "From:", "El ... escribió:", "On ... wrote:"), THE Parser_Inteligente SHALL identificar el mensaje más reciente del hilo (el primer bloque antes de cualquier marcador de respuesta anterior) y extraer título y descripción exclusivamente de ese bloque
9. WHEN el Texto_Fuente contiene marcadores de respuesta dispersos en posiciones no estándar (marcadores ">" de citado, líneas "Re:" intercaladas, bloques citados con prefijo ">"), THE Parser_Inteligente SHALL eliminar todas las líneas citadas (líneas que comienzan con ">") y los marcadores de respuesta antes de extraer la descripción del mensaje principal
10. WHEN el Texto_Fuente contiene un correo reenviado que a su vez contiene un hilo de respuestas, THE Parser_Inteligente SHALL primero localizar el contenido reenviado y luego aplicar las reglas de extracción de hilos sobre dicho contenido para obtener el mensaje más reciente del hilo reenviado

### Requisito 4: Clasificación del subservicio

**Historia de Usuario:** Como administrador de sitios web, quiero que el sistema identifique automáticamente el subservicio más apropiado según el contenido del texto, para reducir el tiempo de clasificación manual.

#### Criterios de Aceptación

1. WHEN el Texto_Fuente contiene términos que coinciden con el nombre, código, servicio padre o familia de un subservicio activo del contrato activo, THE Parser_Inteligente SHALL calcular una puntuación de similitud textual para cada subservicio candidato y sugerir el subservicio con la puntuación más alta
2. IF la puntuación de coincidencia más alta entre todos los subservicios candidatos es inferior al 55%, THEN THE Parser_Inteligente SHALL dejar el campo de subservicio vacío para selección manual
3. WHEN la puntuación de coincidencia del subservicio sugerido es igual o superior al 55%, THE Parser_Inteligente SHALL asignar automáticamente el subservicio y resolver el servicio padre y la familia asociados al subservicio
4. WHEN el Parser_Inteligente resuelve un subservicio, THE Parser_Inteligente SHALL asignar el SLA activo vinculado a dicho subservicio dentro del contrato activo
5. IF el subservicio resuelto no tiene un SLA activo asociado en el contrato activo, THEN THE Parser_Inteligente SHALL dejar el campo de SLA vacío para asignación manual y completar los demás campos (servicio y familia)

### Requisito 5: Extracción de fechas

**Historia de Usuario:** Como administrador de sitios web, quiero que el sistema detecte automáticamente las fechas relevantes del texto (fecha de solicitud y fecha de vencimiento), para mantener la trazabilidad temporal de las solicitudes.

#### Criterios de Aceptación

1. WHEN el Texto_Fuente contiene una fecha en formato español textual (ej: "16 de mayo de 2025") o en formato numérico (dd/mm/yyyy, dd-mm-yyyy), THE Parser_Inteligente SHALL interpretarla como fecha de creación de la solicitud
2. WHEN el Texto_Fuente contiene una fecha en el encabezado "Fecha:" o "Date:" de un correo electrónico, THE Parser_Inteligente SHALL interpretarla como fecha de creación de la solicitud con prioridad sobre fechas encontradas en el cuerpo del texto
3. WHEN el Texto_Fuente contiene múltiples fechas candidatas para fecha de creación, THE Parser_Inteligente SHALL seleccionar la fecha del encabezado de correo si existe, o la primera fecha encontrada en el cuerpo del texto en caso contrario
4. WHEN el Texto_Fuente contiene frases indicativas de plazo (tales como "fecha límite", "plazo", "antes del", "a más tardar", "vence el", "deadline"), seguidas o precedidas de una fecha, THE Parser_Inteligente SHALL asignar dicha fecha como fecha de vencimiento (due_date)
5. IF el Texto_Fuente no contiene frases indicativas de plazo, THEN THE Parser_Inteligente SHALL dejar el campo due_date vacío
6. IF el Parser_Inteligente no puede identificar una fecha de creación en el texto, THEN THE Parser_Inteligente SHALL utilizar la fecha y hora actual del servidor como fecha de creación

### Requisito 6: Extracción de URLs y rutas web

**Historia de Usuario:** Como administrador de sitios web, quiero que el sistema detecte automáticamente las URLs mencionadas en el texto, para asociarlas como rutas web de la solicitud.

#### Criterios de Aceptación

1. WHEN el Texto_Fuente contiene URLs (http, https), THE Parser_Inteligente SHALL extraerlas y asignarlas al campo web_routes
2. THE Parser_Inteligente SHALL extraer un máximo de 8 URLs del Texto_Fuente
3. THE Parser_Inteligente SHALL eliminar URLs duplicadas antes de asignarlas
4. IF el Texto_Fuente no contiene URLs, THEN THE Parser_Inteligente SHALL dejar el campo web_routes vacío para entrada manual

### Requisito 7: Determinación del nivel de criticidad

**Historia de Usuario:** Como administrador de sitios web, quiero que el sistema infiera el nivel de criticidad de la solicitud según el tono y contenido del texto, para priorizar adecuadamente las solicitudes.

#### Criterios de Aceptación

1. WHEN el Texto_Fuente contiene palabras indicativas de criticidad máxima (crítico, critical, emergencia, emergency, sistema caído, system down), THE Parser_Inteligente SHALL asignar el nivel de criticidad CRITICA
2. WHEN el Texto_Fuente contiene palabras indicativas de urgencia (urgente, urgent, inmediato, immediate, lo antes posible, ASAP), THE Parser_Inteligente SHALL asignar el nivel de criticidad URGENTE
3. WHEN el Texto_Fuente contiene palabras indicativas de prioridad alta (prioridad alta, high priority, importante, important, a la brevedad), THE Parser_Inteligente SHALL asignar el nivel de criticidad ALTA
4. WHEN el Texto_Fuente contiene palabras indicativas de baja prioridad (cuando puedas, sin prisa, baja prioridad, low priority, no urgente), THE Parser_Inteligente SHALL asignar el nivel de criticidad BAJA
5. IF el Texto_Fuente no contiene indicadores de criticidad reconocidos, THEN THE Parser_Inteligente SHALL asignar el nivel de criticidad MEDIA por defecto
6. IF el Texto_Fuente contiene indicadores de múltiples niveles de criticidad, THEN THE Parser_Inteligente SHALL asignar el nivel más alto detectado
7. THE Parser_Inteligente SHALL realizar la detección de indicadores de criticidad sin distinguir entre mayúsculas y minúsculas

### Requisito 8: Generación automática de tareas y subtareas

**Historia de Usuario:** Como desarrollador y administrador de sitios web, quiero que el sistema genere automáticamente las tareas y subtareas necesarias para resolver la solicitud, para poder planificar mi trabajo de forma inmediata.

#### Criterios de Aceptación

1. WHEN el Texto_Fuente contiene una lista de acciones o pasos a realizar (líneas que inician con viñetas como *, -, •, o numeración como 1., 2., a), b)), THE Parser_Inteligente SHALL generar una subtarea por cada elemento de la lista, utilizando el texto del elemento como título de la subtarea, hasta un máximo de 20 subtareas por tarea
2. WHEN el Texto_Fuente no contiene una lista explícita de acciones, THE Parser_Inteligente SHALL generar exactamente una tarea con el título derivado del asunto del correo o, en su ausencia, de la primera oración significativa de la descripción, limitado a 255 caracteres
3. WHEN el Parser_Inteligente genera subtareas y el Texto_Fuente no menciona duraciones explícitas para ellas, THE Parser_Inteligente SHALL asignar un tiempo estimado por defecto de 25 minutos a cada subtarea
4. THE Parser_Inteligente SHALL asignar prioridad "medium" a las tareas y subtareas generadas por defecto
5. THE Parser_Inteligente SHALL asignar tipo "regular" a las tareas generadas por defecto
6. WHEN el Texto_Fuente menciona duraciones explícitas junto a un elemento de lista (ej: "30 minutos", "2 horas", "15 min", "1h"), THE Parser_Inteligente SHALL utilizar esas duraciones convertidas a minutos como tiempo estimado de las subtareas correspondientes, con un valor mínimo de 5 minutos y un máximo de 480 minutos
7. WHEN el Parser_Inteligente genera subtareas para una tarea, THE Parser_Inteligente SHALL calcular el campo estimated_hours de la tarea padre como la suma de los estimated_minutes de todas sus subtareas dividida entre 60
8. IF un elemento de la lista tiene un texto menor a 3 caracteres o mayor a 255 caracteres, THEN THE Parser_Inteligente SHALL descartar ese elemento y no generar subtarea para él

### Requisito 9: Soporte para formato libre (no estructurado)

**Historia de Usuario:** Como administrador de sitios web, quiero que el sistema interprete textos sin estructura predefinida (mensajes informales, correos sin formato estándar), para poder procesar solicitudes que no siguen la plantilla actual.

#### Criterios de Aceptación

1. WHEN el Texto_Fuente está en Formato_Libre, THE Parser_Inteligente SHALL extraer los campos de la Solicitud_de_Servicio (título, descripción, solicitante, subservicio, fechas, URLs, criticidad) sin requerir que aparezcan en un orden específico
2. WHEN el Texto_Fuente contiene información irrelevante (saludos, despedidas, firmas, disclaimers legales, marcadores de citado, encabezados repetidos de hilos de correo), THE Parser_Inteligente SHALL ignorar dicho contenido y extraer únicamente la información asociada a los campos de la Solicitud_de_Servicio
3. WHEN el Texto_Fuente está en Formato_Estructurado (formato predefinido existente), THE Parser_Inteligente SHALL procesarlo utilizando el mecanismo de parsing exacto existente
4. WHEN el Texto_Fuente está en Formato_Libre, THE Parser_Inteligente SHALL intentar la extracción de cada campo definido en la Solicitud_de_Servicio (título, descripción, solicitante, subservicio, fechas, URLs, criticidad, tareas) y reportar un Nivel_de_Confianza por cada campo extraído
5. THE Parser_Inteligente SHALL aceptar texto con un mínimo de 20 caracteres y un máximo de 50000 caracteres para intentar la interpretación
6. IF el Texto_Fuente está en Formato_Libre y el Parser_Inteligente no logra extraer al menos el título o la descripción, THEN THE Parser_Inteligente SHALL retornar un error indicando que no se pudo extraer información suficiente del texto proporcionado
7. WHEN el Texto_Fuente contiene contenido pegado en orden arbitrario (encabezados después del cuerpo, firmas intercaladas con contenido, fechas dispersas en posiciones no estándar), THE Parser_Inteligente SHALL analizar el texto completo identificando cada fragmento por su tipo semántico (encabezado, cuerpo, firma, fecha, URL) independientemente de su posición en el texto
8. WHEN el Texto_Fuente contiene contenido duplicado (párrafos repetidos, encabezados que aparecen múltiples veces, bloques de texto idénticos producto de copiar y pegar múltiples veces), THE Parser_Inteligente SHALL deduplicar el contenido antes de la extracción, conservando una única instancia de cada bloque de información relevante
9. WHEN el Texto_Fuente contiene una mezcla de fragmentos de correo electrónico y mensajes de WhatsApp en el mismo texto pegado, THE Parser_Inteligente SHALL identificar y separar los fragmentos por canal, y extraer la información del fragmento que contenga mayor cantidad de datos relevantes para la Solicitud_de_Servicio
10. WHEN el Texto_Fuente contiene marcadores de respuesta dispersos (líneas con ">", ">>", ">>>", prefijos "Re:", "RE:", "Fwd:", "Rv:" intercalados en el cuerpo del texto), THE Parser_Inteligente SHALL eliminar los marcadores de citado y los prefijos de respuesta antes de procesar el contenido, tratando el texto limpio resultante como entrada para la extracción
11. WHEN el Texto_Fuente contiene caracteres de formato residual producto del copiado (tabulaciones excesivas, múltiples saltos de línea consecutivos, espacios no separables, caracteres de control Unicode), THE Parser_Inteligente SHALL normalizar el texto eliminando caracteres de control, colapsando múltiples saltos de línea consecutivos a un máximo de dos, y reemplazando tabulaciones por espacios simples antes de la extracción
12. WHEN el Texto_Fuente contiene múltiples correos pegados consecutivamente sin separación clara (varios bloques con encabezados "De:", "Para:", "Asunto:" repetidos), THE Parser_Inteligente SHALL identificar los límites entre correos individuales, seleccionar el correo más reciente basándose en la fecha del encabezado, y extraer la información exclusivamente de ese correo

### Requisito 10: Interfaz de usuario para importación de texto

**Historia de Usuario:** Como administrador de sitios web, quiero tener un área de texto donde pueda pegar el contenido del correo o WhatsApp y ver los resultados de la interpretación antes de crear la solicitud, para verificar y corregir la información extraída.

#### Criterios de Aceptación

1. THE Sistema SHALL proporcionar un área de texto en el formulario de creación de solicitudes donde el usuario pueda pegar el Texto_Fuente, con un mínimo requerido de 20 caracteres para habilitar la acción de interpretación
2. WHEN el usuario activa la acción de interpretar texto, THE Sistema SHALL mostrar un indicador de procesamiento y, dentro de un máximo de 30 segundos, presentar el formulario de creación pre-llenado con los datos extraídos por el Parser_Inteligente
3. IF la interpretación del Texto_Fuente falla, THEN THE Sistema SHALL mostrar un mensaje de error que indique la causa específica del fallo (texto demasiado corto, subservicio no identificado, o ausencia de contrato activo) y mantener el Texto_Fuente original en el área de texto para que el usuario pueda corregirlo y reintentar
4. THE Sistema SHALL permitir al usuario modificar cualquier campo pre-llenado antes de crear la solicitud
5. IF el Parser_Inteligente no puede resolver el solicitante contra la base de datos existente, THEN THE Sistema SHALL mostrar un aviso visible junto al campo de solicitante indicando el nombre detectado y que se creará un nuevo solicitante al enviar el formulario
6. WHEN la interpretación se completa exitosamente, THE Sistema SHALL indicar visualmente cuáles campos fueron pre-llenados por el Parser_Inteligente para distinguirlos de los campos vacíos que requieren entrada manual

### Requisito 11: Manejo de errores y casos límite

**Historia de Usuario:** Como administrador de sitios web, quiero que el sistema maneje adecuadamente los textos que no puede interpretar completamente, para que siempre pueda crear la solicitud aunque sea con intervención manual parcial.

#### Criterios de Aceptación

1. IF el Texto_Fuente tiene menos de 20 caracteres, THEN THE Parser_Inteligente SHALL retornar un error indicando que el texto es demasiado corto y no procesar ningún campo
2. IF el Parser_Inteligente no puede identificar el solicitante en el texto, THEN THE Parser_Inteligente SHALL continuar la interpretación, retornar los demás campos extraídos exitosamente y dejar el campo de solicitante vacío para selección manual
3. IF el Parser_Inteligente no puede identificar el subservicio con una puntuación de coincidencia mínima del 55%, THEN THE Parser_Inteligente SHALL continuar la interpretación, retornar los demás campos extraídos exitosamente y dejar el campo de subservicio vacío para selección manual
4. IF el espacio de trabajo activo no tiene un contrato activo, THEN THE Parser_Inteligente SHALL retornar un error indicando la ausencia de contrato y no procesar ningún campo
5. IF el Texto_Fuente excede 50000 caracteres, THEN THE Parser_Inteligente SHALL retornar un error indicando que el texto excede el límite máximo permitido
6. IF la interpretación del Texto_Fuente no finaliza en un máximo de 30 segundos, THEN THE Sistema SHALL cancelar la operación y mostrar un mensaje de error indicando que la interpretación excedió el tiempo límite
7. WHEN ocurre un error inesperado no contemplado en los criterios anteriores durante la interpretación, THE Sistema SHALL registrar en el log el tipo de excepción y el identificador del espacio de trabajo, y mostrar al usuario un mensaje de error indicando que ocurrió un problema durante la interpretación sin exponer detalles técnicos internos

### Requisito 12: Compatibilidad con el mecanismo existente

**Historia de Usuario:** Como administrador de sitios web, quiero que el nuevo parser sea compatible con el formato estructurado que ya funciona, para no perder la funcionalidad actual al implementar la mejora.

#### Criterios de Aceptación

1. THE Parser_Inteligente SHALL detectar automáticamente si el Texto_Fuente sigue el Formato_Estructurado existente antes de aplicar cualquier algoritmo de interpretación
2. WHEN el Texto_Fuente sigue el Formato_Estructurado, THE Parser_Inteligente SHALL procesarlo con el algoritmo exacto existente y producir una salida idéntica (misma estructura de payload y meta) a la que produciría el servicio original para la misma entrada
3. WHEN el Texto_Fuente no sigue el Formato_Estructurado, THE Parser_Inteligente SHALL aplicar los algoritmos de interpretación de formato libre
4. THE Parser_Inteligente SHALL mantener la misma interfaz pública del método `parseToFormData` con la misma firma (parámetros: string plainText, int companyId, int|null requestedBy; retorno: array) para garantizar compatibilidad con el controlador existente sin requerir cambios en los consumidores
5. IF el Texto_Fuente sigue el Formato_Estructurado y contiene datos inválidos, THEN THE Parser_Inteligente SHALL lanzar las mismas excepciones de validación con los mismos mensajes que el servicio original lanzaría para esa entrada
