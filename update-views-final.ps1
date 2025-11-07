# Script para actualizar vistas a la nueva estructura de componentes
$updatedViews = @()

function Update-ViewComponents {
    param($filePath)

    $content = Get-Content $filePath -Raw
    $originalContent = $content

    # Mapeo de componentes antiguos a nuevos
    $replacements = @{
        # UI Components - actualizar a nueva estructura
        '<x-primary-button' = '<x-ui.buttons.primary-button'
        '<x-secondary-button' = '<x-ui.buttons.secondary-button'
        '<x-danger-button' = '<x-ui.buttons.danger-button'
        '<x-text-input' = '<x-ui.forms.text-input'
        '<x-input-label' = '<x-ui.forms.input-label'
        '<x-input-error' = '<x-ui.forms.input-error'
        '<x-modal' = '<x-ui.overlays.modal'
        '<x-dropdown' = '<x-ui.overlays.dropdown'
        '<x-dropdown-link' = '<x-ui.overlays.dropdown-link'

        # Core Components - actualizar a nueva estructura
        '<x-application-logo' = '<x-core.layout.application-logo'
        '<x-nav-link' = '<x-core.navigation.nav-link'
        '<x-responsive-nav-link' = '<x-core.navigation.responsive-nav-link'
        '<x-auth-session-status' = '<x-core.feedback.auth-session-status'
        '<x-alerts' = '<x-core.feedback.alerts'

        # Service Requests - OPCIONAL: mantener compatibilidad o actualizar
        # '<x-service-requests.display' = '<x-modules.service-requests.features.view-request'
        # '<x-service-requests.form' = '<x-modules.service-requests.features.create-request'
        # '<x-service-requests.modals' = '<x-modules.service-requests.modals'
        # '<x-service-requests.layout' = '<x-modules.service-requests.features'
    }

    foreach ($replacement in $replacements.GetEnumerator()) {
        $content = $content -replace $replacement.Key, $replacement.Value
    }

    if ($content -ne $originalContent) {
        Set-Content -Path $filePath -Value $content
        $updatedViews += $filePath
        Write-Host "✅ Actualizado: $filePath" -ForegroundColor Green
    }
}

Write-Host "Iniciando actualización de vistas..." -ForegroundColor Yellow

# Actualizar vistas de Auth
Get-ChildItem "resources/views/auth/*.blade.php" | ForEach-Object {
    Update-ViewComponents $_.FullName
}

# Actualizar vistas de Profile
Get-ChildItem "resources/views/profile/**/*.blade.php" | ForEach-Object {
    Update-ViewComponents $_.FullName
}

# Actualizar vistas de Layout
Get-ChildItem "resources/views/layouts/*.blade.php" | ForEach-Object {
    Update-ViewComponents $_.FullName
}

# Actualizar vistas críticas de Service Requests
@(
    "resources/views/service-requests/create.blade.php",
    "resources/views/service-requests/show.blade.php"
) | ForEach-Object {
    if (Test-Path $_) {
        Update-ViewComponents $_
    }
}

Write-Host "`n=== RESUMEN DE ACTUALIZACIONES ===" -ForegroundColor Cyan
Write-Host "Vistas actualizadas: $($updatedViews.Count)" -ForegroundColor Green
$updatedViews | ForEach-Object { Write-Host "  - $_" -ForegroundColor White }

Write-Host "`n¡Actualización completada!" -ForegroundColor Green
Write-Host "Los componentes de Service Requests mantienen compatibilidad via aliases." -ForegroundColor Yellow
