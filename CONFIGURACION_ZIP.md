# Habilitación de la Extensión ZIP para el Módulo de Reportes

## Problema
El módulo de reportes por rango de tiempo requiere la extensión `php-zip` para generar archivos ZIP que incluyan las evidencias de las solicitudes.

## Solución para XAMPP en Windows

### Paso 1: Ubicar el archivo php.ini
1. Abrir el Panel de Control de XAMPP
2. Hacer clic en "Config" junto a Apache
3. Seleccionar "PHP (php.ini)"

### Paso 2: Habilitar la extensión
1. Buscar la línea que contiene: `;extension=zip`
2. Remover el punto y coma (`;`) al inicio de la línea
3. La línea debe quedar así: `extension=zip`

### Paso 3: Reiniciar Apache
1. En el Panel de Control de XAMPP, hacer clic en "Stop" junto a Apache
2. Hacer clic en "Start" para reiniciar Apache

### Paso 4: Verificar la instalación
Ejecutar este comando en la terminal desde la carpeta del proyecto:
```bash
php -r "echo class_exists('ZipArchive') ? 'ZIP habilitado correctamente' : 'ZIP no disponible';"
```

## Solución alternativa si no se puede habilitar ZIP

Si no es posible habilitar la extensión ZIP, los usuarios pueden:

1. Generar el reporte en formato PDF para obtener el análisis completo
2. Generar el reporte en formato Excel para obtener los datos tabulares
3. Descargar evidencias individuales desde las solicitudes específicas

## Notas técnicas

- La extensión ZIP es necesaria solo para la funcionalidad de descarga masiva de evidencias
- Los formatos PDF y Excel funcionan independientemente de esta extensión
- El sistema detecta automáticamente la disponibilidad de ZIP y muestra mensajes de error apropiados

## Verificación de otras dependencias

Para verificar que todas las dependencias estén correctamente instaladas:

```bash
php -r "require 'vendor/autoload.php'; 
echo 'ZipArchive: ' . (class_exists('ZipArchive') ? 'OK' : 'NO') . PHP_EOL; 
echo 'Excel: ' . (class_exists('Maatwebsite\Excel\Facades\Excel') ? 'OK' : 'NO') . PHP_EOL; 
echo 'PDF: ' . (class_exists('Barryvdh\DomPDF\Facade\Pdf') ? 'OK' : 'NO') . PHP_EOL;"
```

Resultado esperado:
```
ZipArchive: OK
Excel: OK
PDF: OK
```
