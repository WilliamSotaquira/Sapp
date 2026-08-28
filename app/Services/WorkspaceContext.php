<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contract;

/**
 * Fuente única de verdad para el contexto de trabajo activo.
 *
 * Modelo orientado a CONTRATO (unidad de cumplimiento):
 *  - El contrato activo es el contexto principal (current_contract_id).
 *  - La entidad (company) se DERIVA del contrato activo.
 *
 * Compatibilidad: durante la migración, si aún no hay contrato en sesión pero
 * sí una entidad (current_company_id legacy), se deriva el contrato activo de
 * esa entidad. Los accesores id()/company() siguen devolviendo la entidad para
 * no romper el código existente que aún razona por company_id.
 *
 * En contexto HTTP se alimenta desde la sesión.
 * En contexto CLI/Queue/Test se puede setear manualmente con ->set()/->setContract().
 */
class WorkspaceContext
{
    /** Override manual de contrato (CLI/Queue/Test). */
    protected ?int $overrideContractId = null;

    /** Override manual de entidad (compatibilidad CLI/Queue/Test). */
    protected ?int $overrideCompanyId = null;

    // =====================================================================
    // CONTRATO (contexto principal)
    // =====================================================================

    /**
     * ID del contrato activo. Prioridad:
     *  1) override manual
     *  2) sesión current_contract_id
     *  3) contrato activo de la entidad en sesión (compatibilidad legacy)
     */
    public function contractId(): ?int
    {
        if ($this->overrideContractId !== null) {
            return $this->overrideContractId;
        }

        $sessionContractId = session('current_contract_id');
        if ($sessionContractId) {
            return (int) $sessionContractId;
        }

        // Compatibilidad: derivar el contrato activo desde la entidad legacy.
        $legacyCompanyId = $this->overrideCompanyId ?? session('current_company_id');
        if ($legacyCompanyId) {
            $activeContractId = Company::where('id', $legacyCompanyId)->value('active_contract_id');
            return $activeContractId ? (int) $activeContractId : null;
        }

        return null;
    }

    /**
     * Modelo Contract del contexto activo.
     */
    public function contract(): ?Contract
    {
        $id = $this->contractId();

        return $id ? Contract::with('company')->find($id) : null;
    }

    /**
     * Setear manualmente el contrato activo (CLI/Queue/Test).
     */
    public function setContract(?int $contractId): static
    {
        $this->overrideContractId = $contractId;
        $this->overrideCompanyId = null;

        return $this;
    }

    /**
     * Cambiar el contrato activo en sesión (HTTP).
     * Mantiene sincronizada la entidad legacy para compatibilidad.
     */
    public function switchToContract(int $contractId): static
    {
        $companyId = Contract::where('id', $contractId)->value('company_id');

        session([
            'current_contract_id' => $contractId,
            'current_company_id' => $companyId ? (int) $companyId : null,
        ]);

        $this->overrideContractId = null;
        $this->overrideCompanyId = null;

        return $this;
    }

    // =====================================================================
    // ENTIDAD (derivada del contrato)
    // =====================================================================

    /**
     * ID de la entidad activa, derivada del contrato. Prioridad:
     *  1) entidad del contrato activo
     *  2) override/sesión legacy (compatibilidad)
     */
    public function companyId(): ?int
    {
        $contractId = ($this->overrideContractId !== null || session('current_contract_id'))
            ? $this->contractId()
            : null;

        if ($contractId) {
            $companyId = Contract::where('id', $contractId)->value('company_id');
            if ($companyId) {
                return (int) $companyId;
            }
        }

        // Compatibilidad legacy.
        if ($this->overrideCompanyId !== null) {
            return $this->overrideCompanyId;
        }

        $sessionCompanyId = session('current_company_id');
        return $sessionCompanyId ? (int) $sessionCompanyId : null;
    }

    /**
     * Modelo Company de la entidad activa (para theming, personas, departamentos).
     */
    public function company(): ?Company
    {
        $id = $this->companyId();

        return $id ? Company::with('activeContract')->find($id) : null;
    }

    // =====================================================================
    // COMPATIBILIDAD (API previa basada en entidad)
    // =====================================================================

    /**
     * @deprecated Usar companyId(). Se mantiene por compatibilidad.
     * Devuelve el ID de la entidad activa.
     */
    public function id(): ?int
    {
        return $this->companyId();
    }

    /**
     * @deprecated Usar setCompany()/setContract(). Setea la entidad (compatibilidad).
     */
    public function set(?int $companyId): static
    {
        $this->overrideCompanyId = $companyId;
        $this->overrideContractId = null;

        return $this;
    }

    /**
     * Resetear overrides (vuelve a usar sesión).
     */
    public function reset(): static
    {
        $this->overrideContractId = null;
        $this->overrideCompanyId = null;

        return $this;
    }

    /**
     * ¿Hay un contexto activo? (contrato o entidad)
     */
    public function isActive(): bool
    {
        return $this->contractId() !== null || $this->companyId() !== null;
    }

    /**
     * @deprecated Usar switchToContract(). Cambia la entidad en sesión (compatibilidad).
     */
    public function switchTo(int $companyId): static
    {
        session([
            'current_company_id' => $companyId,
            // Sincronizar el contrato activo de esa entidad.
            'current_contract_id' => Company::where('id', $companyId)->value('active_contract_id'),
        ]);

        $this->overrideContractId = null;
        $this->overrideCompanyId = null;

        return $this;
    }

    /**
     * Ejecutar un callback en el contexto de otra entidad (compatibilidad).
     */
    public function runAs(int $companyId, callable $callback): mixed
    {
        $prevContract = $this->overrideContractId;
        $prevCompany = $this->overrideCompanyId;

        $this->overrideCompanyId = $companyId;
        $this->overrideContractId = null;

        try {
            return $callback();
        } finally {
            $this->overrideContractId = $prevContract;
            $this->overrideCompanyId = $prevCompany;
        }
    }

    /**
     * Ejecutar un callback en el contexto de otro contrato.
     */
    public function runAsContract(int $contractId, callable $callback): mixed
    {
        $prevContract = $this->overrideContractId;
        $prevCompany = $this->overrideCompanyId;

        $this->overrideContractId = $contractId;
        $this->overrideCompanyId = null;

        try {
            return $callback();
        } finally {
            $this->overrideContractId = $prevContract;
            $this->overrideCompanyId = $prevCompany;
        }
    }
}
