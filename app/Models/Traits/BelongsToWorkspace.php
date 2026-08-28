<?php

namespace App\Models\Traits;

use App\Services\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait para modelos con scope de workspace.
 *
 * Por defecto scopea por company_id (entidad), que es lo correcto para
 * personas y organización (Requester, Department, Project, etc.).
 *
 * Un modelo puede optar por scope a nivel de CONTRATO declarando:
 *     protected static string $workspaceScopeColumn = 'contract_id';
 * En ese caso el filtro y la auto-asignación usan el contrato activo.
 *
 * Uso: `use BelongsToWorkspace;`
 */
trait BelongsToWorkspace
{
    /**
     * Columna por la que se scopea. Se resuelve del modelo si define
     * $workspaceScopeColumn; si no, 'company_id' (comportamiento por defecto).
     */
    public static function workspaceScopeColumn(): string
    {
        return property_exists(static::class, 'workspaceScopeColumn')
            ? static::$workspaceScopeColumn
            : 'company_id';
    }

    /**
     * Valor del contexto activo según la columna de scope:
     *  - contract_id  -> contrato activo
     *  - company_id   -> entidad activa (derivada del contrato)
     */
    protected static function workspaceScopeValue(): ?int
    {
        $ctx = app(WorkspaceContext::class);

        return static::workspaceScopeColumn() === 'contract_id'
            ? $ctx->contractId()
            : $ctx->companyId();
    }

    public static function bootBelongsToWorkspace(): void
    {
        // Global scope: filtrar automáticamente por el contexto activo.
        static::addGlobalScope('workspace', function (Builder $query) {
            $value = static::workspaceScopeValue();

            if ($value) {
                $column = static::workspaceScopeColumn();
                $query->where($query->getModel()->qualifyColumn($column), $value);
            }
        });

        // Auto-asignar la columna de scope al crear si no se proporcionó.
        static::creating(function ($model) {
            $column = static::workspaceScopeColumn();

            if (empty($model->{$column})) {
                $model->{$column} = static::workspaceScopeValue();
            }
        });
    }

    /**
     * Inicializar el trait (casts por defecto).
     */
    public function initializeBelongsToWorkspace(): void
    {
        $column = static::workspaceScopeColumn();

        if (!array_key_exists($column, $this->casts ?? [])) {
            $this->casts[$column] = 'integer';
        }
    }

    /**
     * Relación con la empresa/workspace (entidad).
     */
    public function workspace(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }

    /**
     * Scope para consultar en un workspace específico (sin depender de la sesión).
     * Usa la columna de scope del modelo (company_id o contract_id).
     */
    public function scopeForWorkspace(Builder $query, int $value): Builder
    {
        return $query->withoutGlobalScope('workspace')
            ->where($this->qualifyColumn(static::workspaceScopeColumn()), $value);
    }

    /**
     * Scope para consultar sin restricción de workspace.
     */
    public function scopeAcrossWorkspaces(Builder $query): Builder
    {
        return $query->withoutGlobalScope('workspace');
    }
}
