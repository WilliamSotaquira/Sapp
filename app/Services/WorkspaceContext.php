<?php

namespace App\Services;

use App\Models\Company;

/**
 * Fuente única de verdad para el workspace (entidad/empresa) activo.
 *
 * En contexto HTTP se alimenta desde la sesión.
 * En contexto CLI/Queue/Test se puede setear manualmente con ->set($id).
 *
 * Usar: app(WorkspaceContext::class)->id()
 */
class WorkspaceContext
{
    protected ?int $overrideId = null;

    /**
     * Obtener el ID del workspace activo.
     */
    public function id(): ?int
    {
        if ($this->overrideId !== null) {
            return $this->overrideId;
        }

        return session('current_company_id');
    }

    /**
     * Setear manualmente el workspace (útil para artisan, queues, tests).
     */
    public function set(?int $companyId): static
    {
        $this->overrideId = $companyId;

        return $this;
    }

    /**
     * Resetear el override (vuelve a usar sesión).
     */
    public function reset(): static
    {
        $this->overrideId = null;

        return $this;
    }

    /**
     * Obtener el modelo Company del workspace activo.
     */
    public function company(): ?Company
    {
        $id = $this->id();

        if (!$id) {
            return null;
        }

        return Company::with('activeContract')->find($id);
    }

    /**
     * Verificar si hay un workspace activo.
     */
    public function isActive(): bool
    {
        return $this->id() !== null;
    }

    /**
     * Cambiar el workspace en sesión (para HTTP).
     */
    public function switchTo(int $companyId): static
    {
        session(['current_company_id' => $companyId]);
        $this->overrideId = null;

        return $this;
    }

    /**
     * Ejecutar un callback en el contexto de otro workspace.
     * Restaura el workspace original al terminar.
     */
    public function runAs(int $companyId, callable $callback): mixed
    {
        $previousId = $this->overrideId;

        $this->overrideId = $companyId;

        try {
            return $callback();
        } finally {
            $this->overrideId = $previousId;
        }
    }
}
