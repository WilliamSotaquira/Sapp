# 🔗 Vinculación de Solicitudes con Cortes

## 📋 Resumen de Cambios

Se ha implementado la funcionalidad para vincular directamente solicitudes de servicio con cortes de manera manual durante la creación de la solicitud.

---

## ✅ Cambios Realizados

### 1. **Backend - Modelo de Datos**

#### Actualización: `app/Models/ServiceRequest.php`
- Relación `cuts()` ya existía como `belongsToMany`
- La tabla de vinculación `cut_service_request` ya estaba en BD

#### Actualización: `app/Models/Cut.php`
- Relación `serviceRequests()` ya existía como `belongsToMany`

### 2. **Backend - Servicio**

#### Actualización: `app/Services/ServiceRequestService.php`

**Importación:**
```php
use App\Models\Cut;
```

**Método `getCreateFormData()`:**
- Ahora incluye los cortes disponibles ordenados por `start_date` descendente
```php
'cuts' => Cut::orderBy('start_date', 'desc')->get(['id', 'name', 'start_date', 'end_date']),
```

**Método `createServiceRequest()`:**
- Captura `cut_id` del formulario
- Vincula el corte a la solicitud después de crear el registro
```php
// Vincular al corte si se proporcionó
if (!empty($cutId)) {
    $serviceRequest->cuts()->attach($cutId);
}
```

### 3. **Backend - Validación**

#### Actualización: `app/Http/Requests/StoreServiceRequestRequest.php`

**Reglas:**
```php
'cut_id' => 'nullable|exists:cuts,id',
```

**Mensajes:**
```php
'cut_id.exists' => 'El corte seleccionado no es válido.',
```

### 4. **Frontend - Componentes**

#### Actualización: `resources/views/components/service-requests/forms/basic-fields.blade.php`

**Props:**
- Agregada propiedad `cuts` para recibir lista de cortes

**Selector de Cortes:**
- Campo nuevo ubicado después del "Canal de ingreso"
- Selector dropdown con lista de cortes activos
- Muestra nombre del corte y rango de fechas (dd/mm/aaaa)
- Campo opcional con placeholder "Sin corte asignado"
- Incluye texto explicativo

```blade
<!-- Selector de Corte (opcional) -->
<div>
    <label for="cut_id" class="block text-sm font-medium text-gray-700 mb-2">
        Corte <span class="text-gray-500 text-xs">(Opcional)</span>
    </label>
    <select name="cut_id" id="cut_id"
        class="w-full px-4 py-3 border {{ $cutBorderClass }} rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
        <option value="">Sin corte asignado</option>
        @foreach ($cuts as $cut)
            <option value="{{ $cut->id }}" {{ $selectedCutId == $cut->id ? 'selected' : '' }}>
                {{ $cut->name }} ({{ $cut->start_date->format('d/m/Y') }} - {{ $cut->end_date->format('d/m/Y') }})
            </option>
        @endforeach
    </select>
</div>
```

#### Actualización: `resources/views/service-requests/create.blade.php`

- Paso de parámetro `cuts` al componente `basic-fields`

### 5. **Frontend - Visualización**

#### Nuevo Componente: `resources/views/components/service-requests/show/info-cards/cuts-info.blade.php`

Muestra los cortes asociados a una solicitud:
- Lista de cortes con nombre, rango de fechas y notas
- Botón para ver el corte completo (ruta a `/reports/cuts`)
- Mensaje de "No hay cortes asociados" si no hay vínculos
- Diseño responsive y acorde al resto de la UI

#### Actualización: `resources/views/service-requests/show.blade.php`

- Agregado componente `cuts-info` después de la sección de SLA

---

## 🎯 Flujo de Uso

### **Crear Solicitud con Corte:**

1. Navegar a `/service-requests/create`
2. Rellenar campos básicos (título, descripción, etc.)
3. **Seleccionar un corte** en el dropdown (opcional)
4. Completar el resto del formulario
5. Hacer clic en "Crear Solicitud"
6. La solicitud se vinculará automáticamente al corte seleccionado

### **Ver Solicitud Vinculada:**

1. Abrir una solicitud que tenga corte asociado: `/service-requests/{id}`
2. Scroll hacia abajo para ver la sección "Cortes Asociados"
3. Visualizar los cortes relacionados
4. Hacer clic en "Ver" para ir a la página del corte

---

## 🔄 Base de Datos

### Tabla: `cut_service_request` (pivot)

Estructura (ya existente):
```sql
CREATE TABLE cut_service_request (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    cut_id BIGINT UNSIGNED NOT NULL,
    service_request_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (cut_id) REFERENCES cuts(id) ON DELETE CASCADE,
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE
);
```

---

## 📊 Ejemplo de Uso

```php
// Crear solicitud vinculada a corte
$data = [
    'title' => 'Solicitud importante',
    'description' => 'Descripción...',
    'cut_id' => 1, // Vinculado a corte
    // ... otros campos
];

$serviceRequest = $serviceRequestService->createServiceRequest($data);

// Verificar vinculación
$cuts = $serviceRequest->cuts; // Colección de cortes
echo "Cortes vinculados: " . $cuts->count();

// Acceder a solicitudes desde un corte
$cut = Cut::find(1);
$requests = $cut->serviceRequests; // Todas las solicitudes del corte
```

---

## ✨ Características Adicionales

### Ventajas de la Implementación:

✅ **Manual y Flexible:** Usuario puede elegir qué corte (o ninguno)  
✅ **Optional:** El campo es completamente opcional  
✅ **Escalable:** Fácil agregar más cortes en el selector  
✅ **Auditable:** Las vinculaciones quedan registradas en BD  
✅ **Relacionable:** Fácil consultar todas las solicitudes de un corte  
✅ **Visual:** Se muestra claramente en la vista de solicitud  

---

## 🧪 Verificación

Para verificar que todo funciona correctamente:

```bash
# 1. Verificar cortes disponibles
php artisan tinker
>>> App\Models\Cut::all()

# 2. Crear solicitud con corte manualmente
>>> App\Models\ServiceRequest::with('cuts')->first()

# 3. Ver solicitudes de un corte
>>> App\Models\Cut::first()->serviceRequests
```

---

## 📝 Próximas Mejoras (Opcional)

1. **Editar Corte en Solicitud Existente:** Permitir cambiar el corte en la vista de edición
2. **Múltiples Cortes:** Permitir vincular una solicitud a varios cortes
3. **Filtro por Corte:** En listado de solicitudes, filtrar por corte
4. **Reportes:** Generar reportes que agrupen solicitudes por corte
5. **Validaciones:** Validar que la fecha de la solicitud esté dentro del rango del corte

---

**Estado:** ✅ Implementado y listo para usar
**Fecha:** 22 de enero de 2026
