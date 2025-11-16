# 🚀 Comandos para Ejecutar en Producción

## Ya estás conectado al servidor. Ejecuta estos comandos:

### 1. Ir al directorio del proyecto
```bash
cd htdocs/weirdoware-sapp.com
```

### 2. Verificar el estado actual
```bash
pwd
git status
git log --oneline -n 5
```

### 3. Crear backup de base de datos (IMPORTANTE)
```bash
mysqldump -u weirdoware_sapp -p weirdoware_sapp > ~/backup_fix_status_$(date +%Y%m%d_%H%M%S).sql
```

### 4. Poner en modo mantenimiento
```bash
php artisan down --message="Actualizando sistema" --retry=60
```

### 5. Actualizar el código
```bash
git pull origin main
```

### 6. Verificar que se obtuvo el commit correcto
```bash
git log --oneline -n 5
```
Debes ver el commit: **3aea8a0 Fix: Corregir error 'Column status cannot be null'**

### 7. Limpiar cachés
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 8. Optimizar para producción
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 9. Reiniciar queue workers (si aplica)
```bash
php artisan queue:restart
```

### 10. Verificar el fix
```bash
php artisan tinker --execute="echo (new \App\Models\ServiceRequest)->status;"
```
**Debe mostrar:** PENDIENTE

### 11. Activar la aplicación
```bash
php artisan up
```

### 12. Verificar logs
```bash
tail -f storage/logs/laravel.log
```
Presiona `Ctrl+C` para salir

---

## ✅ Prueba Final
1. Abrir el navegador: https://weirdoware-sapp.com
2. Ir a crear solicitud de servicio
3. Completar y enviar el formulario
4. Verificar que NO aparezca el error "Column 'status' cannot be null"

---

## 🔴 Si algo sale mal (Rollback):
```bash
git reset --hard c37f838
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan up
```

---

## 📋 Comando Todo-en-Uno (Despliegue Completo):
```bash
cd htdocs/weirdoware-sapp.com && \
mysqldump -u weirdoware_sapp -p weirdoware_sapp > ~/backup_fix_status_$(date +%Y%m%d_%H%M%S).sql && \
php artisan down --message="Actualizando" --retry=60 && \
git pull origin main && \
php artisan cache:clear && \
php artisan config:clear && \
php artisan route:clear && \
php artisan view:clear && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
php artisan queue:restart && \
php artisan up && \
echo "✅ Despliegue completado. Verifica la aplicación."
```
