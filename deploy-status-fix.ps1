#####################################################################
# Script de Despliegue Automático - Fix Status Null Error (PowerShell)
# Fecha: 16 de noviembre de 2025
# Commit: 3aea8a0
#####################################################################

# Configuración de colores
$ErrorActionPreference = "Stop"

function Print-Status {
    param([string]$Message)
    Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] " -NoNewline -ForegroundColor Blue
    Write-Host $Message
}

function Print-Success {
    param([string]$Message)
    Write-Host "✅ $Message" -ForegroundColor Green
}

function Print-Warning {
    param([string]$Message)
    Write-Host "⚠️  $Message" -ForegroundColor Yellow
}

function Print-Error {
    param([string]$Message)
    Write-Host "❌ $Message" -ForegroundColor Red
}

# Verificar que estamos en el directorio correcto
if (-not (Test-Path "artisan")) {
    Print-Error "No se encuentra el archivo artisan. Asegúrate de estar en la raíz del proyecto Laravel."
    exit 1
}

Print-Status "==================================================================="
Print-Status "Iniciando Despliegue - Fix Status Null Error"
Print-Status "==================================================================="

try {
    # 1. Backup de base de datos
    Print-Status "Paso 1/10: Preparando backup de base de datos..."
    $backupFile = "backup_$(Get-Date -Format 'yyyyMMdd_HHmmss').sql"
    
    # Verificar si existe el directorio de backups
    if (-not (Test-Path "storage\backups")) {
        New-Item -ItemType Directory -Path "storage\backups" -Force | Out-Null
    }
    
    $createBackup = Read-Host "¿Deseas crear un backup de la base de datos? (s/n)"
    if ($createBackup -eq "s") {
        $dbUser = Read-Host "Ingresa el nombre de usuario de MySQL"
        $dbPass = Read-Host "Ingresa la contraseña de MySQL" -AsSecureString
        $dbPassPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($dbPass))
        $dbName = Read-Host "Ingresa el nombre de la base de datos"
        
        $mysqldumpPath = "C:\xampp\mysql\bin\mysqldump.exe"
        if (Test-Path $mysqldumpPath) {
            & $mysqldumpPath -u $dbUser -p"$dbPassPlain" $dbName > "storage\backups\$backupFile"
            Print-Success "Backup creado: storage\backups\$backupFile"
        } else {
            Print-Warning "mysqldump no encontrado en $mysqldumpPath"
        }
    } else {
        Print-Warning "Backup manual requerido antes de continuar"
        $manualBackup = Read-Host "¿Has creado un backup manual? (s/n)"
        if ($manualBackup -ne "s") {
            Print-Error "Por favor crea un backup antes de continuar."
            exit 1
        }
    }

    # 2. Modo mantenimiento
    Print-Status "Paso 2/10: Activando modo mantenimiento..."
    php artisan down --message="Actualizando sistema" --retry=60
    Print-Success "Aplicación en modo mantenimiento"

    # 3. Guardar cambios locales
    Print-Status "Paso 3/10: Guardando cambios locales (si existen)..."
    $gitStatus = git status --porcelain
    if ([string]::IsNullOrWhiteSpace($gitStatus)) {
        Print-Success "No hay cambios locales"
    } else {
        git stash
        Print-Warning "Cambios locales guardados en stash"
    }

    # 4. Actualizar código
    Print-Status "Paso 4/10: Obteniendo últimos cambios del repositorio..."
    git fetch origin
    git pull origin main
    Print-Success "Código actualizado"

    # 5. Verificar commit
    Print-Status "Paso 5/10: Verificando commit..."
    $currentCommit = git rev-parse --short HEAD
    Print-Status "Commit actual: $currentCommit"
    
    $commitHistory = git log --oneline -n 10
    if ($commitHistory -match "3aea8a0") {
        Print-Success "Commit del fix encontrado en el historial"
    } else {
        Print-Warning "No se encontró el commit específico del fix, pero continuando..."
    }

    # 6. Instalar/actualizar dependencias
    Print-Status "Paso 6/10: Verificando dependencias..."
    if (Test-Path "composer.lock") {
        $updateDeps = Read-Host "¿Deseas actualizar dependencias de Composer? (s/n)"
        if ($updateDeps -eq "s") {
            composer install --no-dev --optimize-autoloader --no-interaction
            Print-Success "Dependencias verificadas"
        } else {
            Print-Warning "Saltando actualización de dependencias"
        }
    }

    # 7. Limpiar cachés
    Print-Status "Paso 7/10: Limpiando cachés..."
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    Print-Success "Cachés limpiados"

    # 8. Optimizar para producción
    Print-Status "Paso 8/10: Optimizando para producción..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    Print-Success "Optimizaciones aplicadas"

    # 9. Reiniciar servicios
    Print-Status "Paso 9/10: Reiniciando servicios..."
    
    # Queue workers
    $queueProcesses = Get-Process | Where-Object { $_.ProcessName -like "*php*" -and $_.CommandLine -like "*queue:work*" }
    if ($queueProcesses) {
        php artisan queue:restart
        Print-Success "Queue workers reiniciados"
    }

    $restartServices = Read-Host "¿Deseas reiniciar Apache/servicios? (s/n)"
    if ($restartServices -eq "s") {
        # Intentar reiniciar Apache en XAMPP
        $apachePath = "C:\xampp\apache\bin\httpd.exe"
        if (Test-Path $apachePath) {
            Print-Status "Reiniciando Apache..."
            Stop-Process -Name "httpd" -Force -ErrorAction SilentlyContinue
            Start-Sleep -Seconds 2
            Start-Process $apachePath
            Print-Success "Apache reiniciado"
        }
    }

    # 10. Verificación del fix
    Print-Status "Paso 10/10: Verificando el fix..."
    $verification = php artisan tinker --execute="echo (new \App\Models\ServiceRequest)->status ?? 'ERROR';"
    if ($verification -match "PENDIENTE") {
        Print-Success "Fix verificado correctamente: status por defecto = PENDIENTE"
    } else {
        Print-Warning "No se pudo verificar el fix automáticamente"
    }

    # Activar aplicación
    Print-Status "Activando aplicación..."
    php artisan up
    Print-Success "Aplicación activada"

    Print-Status "==================================================================="
    Print-Success "¡Despliegue completado exitosamente!"
    Print-Status "==================================================================="
    
    Write-Host ""
    Print-Status "Próximos pasos:"
    Write-Host "1. Verificar que la aplicación carga correctamente"
    Write-Host "2. Crear una solicitud de prueba"
    Write-Host "3. Verificar que el historial de estados se registra correctamente"
    Write-Host "4. Revisar logs: Get-Content storage\logs\laravel.log -Tail 50"
    Write-Host ""
    
    Print-Warning "En caso de necesitar hacer rollback:"
    Write-Host "git reset --hard $currentCommit~1"
    Write-Host "php artisan cache:clear; php artisan config:cache"
    Write-Host ""
    
    Print-Status "Backup disponible en: storage\backups\$backupFile"

} catch {
    Print-Error "Error durante el despliegue: $_"
    Print-Warning "Intentando reactivar la aplicación..."
    php artisan up
    exit 1
}
