# ✅ Checklist Rápido de Despliegue en Producción

## 📦 Cambios Listos para Desplegar

**Commits subidos al repositorio:**
- `3aea8a0` - Fix principal del error de status null
- `7add756` - Documentación de despliegue
- `cbf2359` - Scripts automatizados de despliegue

---

## 🚀 OPCIÓN 1: Despliegue Automático (RECOMENDADO)

### En Linux/Unix:
```bash
cd /ruta/del/proyecto
chmod +x deploy-status-fix.sh
./deploy-status-fix.sh
```

### En Windows (PowerShell como Administrador):
```powershell
cd C:\ruta\del\proyecto
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\deploy-status-fix.ps1
```

---

## 🔧 OPCIÓN 2: Despliegue Manual Paso a Paso

### 1. Preparación (2 minutos)
```bash
# Backup de BD
mysqldump -u usuario -p nombre_bd > backup_$(date +%Y%m%d).sql

# Modo mantenimiento
php artisan down --message="Actualizando" --retry=60
```

### 2. Actualización de Código (1 minuto)
```bash
git stash                    # Si hay cambios locales
git pull origin main
git log --oneline -n 3       # Verificar commits
```

### 3. Optimización (2 minutos)
```bash
# Limpiar
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimizar
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. Reiniciar Servicios (1 minuto)
```bash
# Queue (si aplica)
php artisan queue:restart

# PHP-FPM
sudo systemctl restart php-fpm

# Nginx/Apache
sudo systemctl restart nginx
# O
sudo systemctl restart apache2
```

### 5. Activar y Verificar (1 minuto)
```bash
# Activar
php artisan up

# Verificar
php artisan tinker --execute="echo (new \App\Models\ServiceRequest)->status;"
# Debe mostrar: PENDIENTE
```

---

## ✅ Verificación Post-Despliegue

### Prueba Rápida (3 minutos)
1. **Abrir la aplicación** en el navegador
2. **Ir a crear solicitud:** `/service-requests/create`
3. **Completar formulario** con datos de prueba
4. **Enviar solicitud**
5. **Verificar:** Debe crearse sin error "status cannot be null"
6. **Abrir la solicitud** recién creada
7. **Verificar historial:** Debe aparecer "PENDIENTE" como estado inicial

### Revisar Logs
```bash
tail -f storage/logs/laravel.log
```
**Buscar:** No debe haber errores de "Column 'status' cannot be null"

---

## 🔴 Rollback de Emergencia (Si algo sale mal)

### Rollback Rápido
```bash
# Volver código al commit anterior
git reset --hard c37f838

# Limpiar cachés
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# Reiniciar servicios
sudo systemctl restart php-fpm
sudo systemctl restart nginx

# Activar
php artisan up
```

### Restaurar Base de Datos (Si es necesario)
```bash
mysql -u usuario -p nombre_bd < backup_FECHA.sql
```

---

## 📋 Checklist Final

- [ ] ✅ Código subido al repositorio (commits: 3aea8a0, 7add756, cbf2359)
- [ ] 📥 Backup de base de datos creado
- [ ] 🔽 Aplicación en modo mantenimiento
- [ ] 📦 Código actualizado con `git pull`
- [ ] 🗑️ Cachés limpiados
- [ ] ⚡ Optimizaciones aplicadas
- [ ] 🔄 Servicios reiniciados
- [ ] 🟢 Aplicación reactivada
- [ ] ✅ Prueba de creación de solicitud exitosa
- [ ] 📊 Historial de estados funcionando
- [ ] 📝 Sin errores en logs

---

## 📞 Información de Contacto

**En caso de problemas:**
- Revisar: `storage/logs/laravel.log`
- Verificar: Estado de servicios con `systemctl status`
- Rollback: Usar comandos de la sección anterior

---

## 📊 Resumen Técnico

### ¿Qué se corrigió?
- **Error:** `SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'status' cannot be null`
- **Causa:** Campo status era null al crear historial de estados
- **Solución:** Valor por defecto 'PENDIENTE' en modelo + fallback en Observer

### Archivos Modificados
1. `app/Models/ServiceRequest.php` - Agregado `protected $attributes`
2. `app/Observers/ServiceRequestObserver.php` - Agregado fallback

### Impacto
- ✅ **Sin cambios en BD:** Solo código PHP
- ✅ **Sin migraciones:** No requiere `php artisan migrate`
- ✅ **Retrocompatible:** No afecta datos existentes
- ✅ **Tiempo estimado:** 5-10 minutos

---

**Última actualización:** 16 de noviembre de 2025  
**Estado:** ✅ Listo para Producción  
**Prioridad:** Alta (Fix de bug crítico)
