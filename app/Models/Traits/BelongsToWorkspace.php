<?php

namespace App\Models\Traits;

use App\Services\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait para modelos que pertenecen a un workspace (empresa).
 *
 * Agrega automáticamente:
 * - Global scope que filtra por company_id del workspace activo
 * - Auto-asignación de company_id al crear registros
 * - Relación company()
 *
 * Uso: `use BelongsToWorkspace;` en cualquier modelo con columna company_id.
 */
trait BelongsToWorkspace
{
    public static function bootBelongsToWorkspace(): void
    {
        // Global scope: filtrar automáticamente por workspace activo
        static::addGlobalScope('workspace', function (Builder $query) {
            $companyId = app(WorkspaceContext::class)->id();

            if ($companyId) {
                $query->where($query->getModel()->qualifyColumn('company_id'), $companyId);
            }
        });

        // Auto-asignar company_id al crear si no se proporcionó
        static::creating(function ($model) {
            if (empty($model->company_id)) {
                $model->company_id = app(WorkspaceContext::class)->id();
            }
        });
    }

    /**
     * Inicializar el trait (setear valores por defecto si es necesario).
     */
    public function initializeBelongsToWorkspace(): void
    {
        // Asegurar que company_id sea integer en casts
        if (!array_key_exists('company_id', $this->casts ?? [])) {
            $this->casts['company_id'] = 'integer';
        }
    }

    /**
     * Relación con la empresa/workspace.
     */
    public function workspace(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }

    /**
     * Scope para consultar en un workspace específico (sin depender de la sesión).
     */
    public function scopeForWorkspace(Builder $query, int $companyId): Builder
    {
        return $query->withoutGlobalScope('workspace')
            ->where($this->qualifyColumn('company_id'), $companyId);
    }

    /**
     * Scope para consultar sin restricción de workspace.
     */
    public function scopeAcrossWorkspaces(Builder $query): Builder
    {
        return $query->withoutGlobalScope('workspace');
    }
}
