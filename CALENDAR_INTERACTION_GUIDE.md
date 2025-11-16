# 📅 Guía de Interacción con el Calendario

## Funcionalidades Interactivas

### 🔄 Arrastrar y Soltar (Drag & Drop)
Mueve tareas entre diferentes días y horas.

**Cómo usar:**
1. Haz clic en una tarea y mantén presionado
2. Arrastra hacia la celda destino (día + hora)
3. La celda se resaltará en verde
4. Suelta el mouse
5. Confirma el movimiento en el diálogo

**Indicadores visuales:**
- 🔵 Icono grip (⋮⋮) indica que es arrastrable
- 🟢 Celda verde: zona de destino válida
- 👻 Tarea semi-transparente durante el arrastre
- ➕ Icono plus en celdas vacías

---

### 📏 Redimensionar Duración
Ajusta la duración estimada de una tarea visualmente.

**Cómo usar:**
1. Pasa el mouse sobre una tarea
2. Verás una barra gris en la parte inferior
3. Haz clic y arrastra hacia abajo para aumentar duración
4. Arrastra hacia arriba para disminuir duración
5. Suelta el mouse
6. Confirma el cambio

**Cálculo:**
- Cada 30px ≈ 0.25 horas (15 minutos)
- Duración mínima: 15 minutos (0.25h)
- Duración máxima: 24 horas

**Indicadores visuales:**
- 📊 Barra de resize se muestra al hover
- 🔵 Borde azul discontinuo durante resize
- ⏱️ Duración actualizada en tiempo real
- 🟢 Confirmación con checkmark verde

---

### 👆 Click para Ver Detalles
Accede rápidamente a la información completa de una tarea.

**Cómo usar:**
- Haz click en cualquier parte de la tarea (excepto mientras arrastras o redimensionas)
- Se abrirá la vista detallada de la tarea

---

## 🎨 Códigos de Color

### Por Tipo:
- 🔴 **Rojo**: Tareas de Impacto (90 min)
- 🔵 **Azul**: Tareas Regulares (25 min)

### Por Estado:
- ⚫ **Gris**: Pendiente
- 🔵 **Azul**: En Progreso
- 🔴 **Rojo**: Bloqueada
- 🟡 **Amarillo**: En Revisión
- 🟢 **Verde**: Completada

### Por Prioridad:
- 🔴 **Rojo**: Crítica
- 🟠 **Naranja**: Alta
- 🟡 **Amarillo**: Media
- 🟢 **Verde**: Baja

---

## 📝 Historial Automático

Todas las acciones se registran automáticamente en el historial:
- **Mover tarea**: `rescheduled` - Registra fecha/hora anterior y nueva
- **Cambiar duración**: `updated` - Registra duración anterior y nueva
- **Crear tarea**: `created` - Registro inicial
- **Asignar técnico**: `assigned` - Técnico asignado

---

## 💡 Consejos

1. **Planificación semanal**: Usa la vista de semana para organizar todas las tareas
2. **Filtro por técnico**: Selecciona un técnico para ver solo sus tareas
3. **Doble verificación**: Siempre hay confirmación antes de guardar cambios
4. **Recarga automática**: La página se recarga automáticamente después de cambios exitosos
5. **Feedback visual**: Mensajes de éxito/error en la esquina superior derecha

---

## 🚀 Atajos y Trucos

- **Reorganizar rápidamente**: Arrastra varias tareas en secuencia
- **Ajuste fino**: Usa pequeños movimientos verticales para cambios de 15 minutos
- **Vista general**: Cambia entre día/semana/mes según necesites
- **Hoy**: Botón "Hoy" para volver a la fecha actual rápidamente

---

## 🔧 Solución de Problemas

**La tarea no se mueve:**
- Verifica que la celda destino se resalte en verde
- Confirma el diálogo de confirmación
- Revisa la consola del navegador (F12) para errores

**No veo el handle de resize:**
- Pasa el mouse sobre la tarea lentamente
- Verifica que la tarea tenga altura suficiente (>40px)

**Los cambios no se guardan:**
- Verifica conexión a internet
- Revisa que tengas permisos de edición
- Consulta con el administrador del sistema
