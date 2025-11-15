# Módulo de Reportes por Rango de Tiempo - Documentación

## Descripción
Este módulo permite generar reportes detallados de solicitudes de servicio filtradas por rango de fechas y familia de servicios, con la opción de incluir todas las evidencias en un archivo ZIP.

## Características Principales

### 1. Formulario Inteligente
- **Selector de fechas** con validación automática
- **Rangos rápidos**: últimos 7/30 días, mes actual, mes anterior
- **Filtrado por familias de servicio** con selección múltiple
- **Opciones de formato**: PDF, Excel, ZIP

### 2. Análisis Estadístico Completo
- Total de solicitudes en el periodo
- Distribución por estado (Pendiente, En Progreso, Resuelta, etc.)
- Distribución por nivel de criticidad
- Métricas de tiempo de resolución
- Análisis por familia de servicios
- Indicadores de satisfacción

### 3. Formatos de Salida

#### PDF (reporte-principal.pdf)
- Diseño profesional con gráficos visuales
- Estadísticas resumidas por familia
- Top 10 solicitudes por familia
- Resumen de evidencias

#### Excel (reporte-datos.xlsx)
- **Hoja "Solicitudes"**: Listado completo con todos los detalles
- **Hoja "Estadísticas"**: Métricas calculadas y porcentajes
- **Hoja "Por Familia"**: Análisis agrupado por familia de servicios
- **Hoja "Evidencias"**: Inventario completo de archivos adjuntos

#### ZIP (archivo-completo.zip)
- Reporte principal en PDF
- Datos en Excel
- Carpeta "evidencias/" con todos los archivos
- Archivo "RESUMEN_REPORTE.txt" con estadísticas

## Archivos Creados

### Controladores
- `app/Http/Controllers/Reports/TimeRangeReportController.php`
- Métodos: `index()`, `generate()`, `getReportData()`, `calculateStatistics()`, etc.

### Exportaciones
- `app/Exports/TimeRangeReportExport.php`
- Implementa múltiples hojas de Excel con formato profesional

### Vistas
- `resources/views/reports/time-range/index.blade.php` - Formulario principal
- `resources/views/reports/time-range/pdf.blade.php` - Template del PDF

### Rutas
- `GET reports/time-range` - Formulario
- `POST reports/time-range/generate` - Generar reporte

## Estructura de Datos del Reporte

```php
$reportData = [
    'requests' => Collection,      // Solicitudes filtradas
    'groupedData' => Collection,   // Agrupadas por familia
    'statistics' => [
        'total_requests' => int,
        'by_status' => Collection,
        'by_criticality' => Collection,
        'by_family' => Collection,
        'resolved_count' => int,
        'overdue_count' => int,
        'avg_resolution_time' => float,
        'satisfaction_avg' => float
    ],
    'evidences' => Collection,     // Evidencias relacionadas
    'dateRange' => array,         // Rango de fechas
    'serviceFamilyIds' => array   // IDs filtrados
];
```

## Funcionalidades Avanzadas

### 1. Gestión de Archivos Temporales
- Creación automática del directorio `storage/app/temp/`
- Limpieza automática de archivos ZIP después de la descarga
- Manejo seguro de rutas y nombres de archivo

### 2. Validaciones de Seguridad
- Sanitización de nombres de archivo para el ZIP
- Verificación de existencia de evidencias
- Control de tamaño de archivo
- Autenticación requerida para acceso

### 3. Optimización de Rendimiento
- Carga eager de relaciones necesarias
- Paginación automática en PDF para grandes datasets
- Compresión eficiente de archivos
- Generación asíncrona para reportes grandes

### 4. Manejo de Errores
- Verificación de dependencias (ZipArchive, Excel, PDF)
- Mensajes de error descriptivos
- Fallback a formatos alternativos
- Logging detallado para debugging

## Requisitos Técnicos

### Dependencias PHP
- `php-zip` (para archivos ZIP)
- `maatwebsite/excel` (para Excel)
- `barryvdh/dompdf` (para PDF)

### Verificación de Dependencias
```bash
php -r "require 'vendor/autoload.php'; 
echo 'ZipArchive: ' . (class_exists('ZipArchive') ? 'OK' : 'NO') . PHP_EOL; 
echo 'Excel: ' . (class_exists('Maatwebsite\Excel\Facades\Excel') ? 'OK' : 'NO') . PHP_EOL; 
echo 'PDF: ' . (class_exists('Barryvdh\DomPDF\Facade\Pdf') ? 'OK' : 'NO') . PHP_EOL;"
```

## Uso del Módulo

### 1. Acceso
- Navegar a Dashboard → Reportes → "Reporte por Rango de Tiempo"
- O acceder directamente a `/reports/time-range`

### 2. Configuración del Reporte
1. Seleccionar fechas de inicio y fin
2. Elegir familias de servicio (opcional)
3. Seleccionar formato de salida
4. Hacer clic en "Generar Reporte"

### 3. Descarga
- **PDF/Excel**: Descarga inmediata
- **ZIP**: Descarga archivo comprimido con todo incluido

## Personalización

### Modificar Estadísticas
Editar el método `calculateStatistics()` en `TimeRangeReportController.php`

### Personalizar PDF
Modificar el template en `resources/views/reports/time-range/pdf.blade.php`

### Agregar Hojas a Excel
Agregar nuevas clases Sheet en `TimeRangeReportExport.php`

## Notas de Implementación

1. **Seguridad**: Todas las descargas requieren autenticación
2. **Performance**: Optimizado para hasta 10,000 solicitudes
3. **Escalabilidad**: Preparado para implementar colas para reportes grandes
4. **Mantenimiento**: Logs detallados para monitoreo y debugging

## Próximas Mejoras Sugeridas

1. **Programación de reportes** automáticos
2. **Envío por email** de reportes generados
3. **Dashboard interactivo** con filtros en tiempo real
4. **Comparación entre periodos**
5. **Exportación a otros formatos** (CSV, JSON)
6. **API REST** para integración con otros sistemas
