# 🚀 Despliegue en Producción - Fix Status Null Error

## 📋 Descripción del Fix
Corrección del error `SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'status' cannot be null` al crear solicitudes de servicio.

**Commit:** `3aea8a0`  
**Fecha:** 16 de noviembre de 2025

---

## 🔧 Archivos Modificados

1. **`app/Models/ServiceRequest.php`**
   - Agregado `protected $attributes` con valor por defecto `'PENDIENTE'` para el campo `status`

2. **`app/Observers/ServiceRequestObserver.php`**
   - Agregado fallback en método `created()` para garantizar que `status` nunca sea null

---

## 📝 Pasos para Desplegar en Producción

### 1. Conectarse al Servidor de Producción
```bash
ssh usuario@servidor-produccion
cd /ruta/del/proyecto
```

### 2. Hacer Backup de la Base de Datos (IMPORTANTE)
```bash
php artisan backup:run
# O manualmente:
mysqldump -u usuario -p nombre_bd > backup_$(date +%Y%m%d_%H%M%S).sql
```

### 3. Detener la Aplicación (Opcional pero Recomendado)
```bash
php artisan down --message="Actualizando sistema" --retry=60
```

### 4. Actualizar el Código
```bash
# Guardar cambios locales si los hay
git stash

# Obtener últimos cambios
git pull origin main

# Si es necesario, restaurar cambios locales
# git stash pop
```

### 5. Verificar que Estamos en el Commit Correcto
```bash
git log --oneline -n 5
# Debe aparecer: 3aea8a0 Fix: Corregir error 'Column status cannot be null' en service_request_status_histories
```

### 6. Limpiar Caché de Laravel
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 7. Optimizar para Producción
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 8. Reiniciar Servicios (si aplica)
```bash
# Si usa Queue Workers
php artisan queue:restart

# Si usa PHP-FPM
sudo systemctl restart php-fpm
# O
sudo service php8.2-fpm restart

# Si usa Apache
sudo systemctl restart apache2
# O
sudo service apache2 restart

# Si usa Nginx
sudo systemctl restart nginx
```

### 9. Activar la Aplicación
```bash
php artisan up
```

### 10. Verificar el Funcionamiento
```bash
# Verificar que el modelo tiene el atributo correcto
php artisan tinker --execute="echo (new \App\Models\ServiceRequest)->status;"
# Debe retornar: PENDIENTE
```

---

## ✅ Pruebas Post-Despliegue

### 1. Prueba de Creación de Solicitud
- Ir a la página de creación de solicitudes
- Completar el formulario
- Crear una nueva solicitud
- **Resultado Esperado:** La solicitud se crea exitosamente sin errores

### 2. Verificar Historial de Estados
- Abrir la solicitud recién creada
- Verificar que aparece en el historial el estado inicial "PENDIENTE"
- **Resultado Esperado:** El historial muestra correctamente el estado inicial

### 3. Revisar Logs
```bash
tail -f storage/logs/laravel.log
```
- **Resultado Esperado:** No debe haber errores relacionados con "Column 'status' cannot be null"

---

## 🔄 Plan de Rollback (Si Algo Sale Mal)

### Opción 1: Volver al Commit Anterior
```bash
git reset --hard c37f838
php artisan cache:clear
php artisan config:cache
php artisan route:cache
# Reiniciar servicios
```

### Opción 2: Restaurar desde Backup
```bash
# Restaurar base de datos
mysql -u usuario -p nombre_bd < backup_FECHA.sql

# Volver al código anterior
git reset --hard c37f838

# Limpiar cachés
php artisan cache:clear
php artisan config:cache
```

---

## 📊 Impacto Esperado

### ✅ Beneficios
- **Solución definitiva** al error de constraint violation
- **Mejora la experiencia del usuario** al crear solicitudes
- **Previene errores** en el módulo de historial de estados
- **No requiere migraciones** de base de datos adicionales

### ⚠️ Consideraciones
- **Sin cambios en la base de datos:** Solo cambios en código PHP
- **Sin impacto en datos existentes:** Los registros actuales no se ven afectados
- **Retrocompatible:** No rompe funcionalidad existente

---

## 🐛 Detalles Técnicos del Fix

### Problema Original
```php
// El Observer intentaba crear historial con status = null
ServiceRequestStatusHistory::create([
    'service_request_id' => $serviceRequest->id,
    'status' => $serviceRequest->status, // ❌ Podía ser null
    // ...
]);
```

### Solución Implementada

**1. Modelo (ServiceRequest.php):**
```php
protected $attributes = [
    'status' => 'PENDIENTE', // ✅ Valor por defecto
];
```

**2. Observer (ServiceRequestObserver.php):**
```php
public function created(ServiceRequest $serviceRequest): void
{
    $status = $serviceRequest->status ?? 'PENDIENTE'; // ✅ Fallback
    $this->logStatusChange($serviceRequest, null, $status, 'Solicitud creada');
}
```

---

## 📞 Contacto de Soporte

Si encuentras algún problema durante el despliegue:
- Revisar logs: `storage/logs/laravel.log`
- Verificar estado de servicios: `systemctl status php-fpm nginx`
- Contactar al equipo de desarrollo

---

## ✅ Checklist de Despliegue

- [ ] Backup de base de datos realizado
- [ ] Aplicación en modo mantenimiento
- [ ] Código actualizado (git pull)
- [ ] Commit correcto verificado (3aea8a0)
- [ ] Cachés limpiados
- [ ] Cachés optimizados
- [ ] Servicios reiniciados
- [ ] Aplicación activada
- [ ] Prueba de creación exitosa
- [ ] Historial de estados funcional
- [ ] Sin errores en logs

---

**Fecha de Creación:** 16 de noviembre de 2025  
**Versión:** 1.0  
**Estado:** ✅ Listo para Producción
