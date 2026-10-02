<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    /**
     * El admin pasa todas las abilities; el resto se evalúa por propiedad.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, ServiceRequest $sr): bool
    {
        return $this->ownsSr($user, $sr);
    }

    public function update(User $user, ServiceRequest $sr): bool
    {
        return $this->ownsSr($user, $sr);
    }

    // Acciones de workflow que el técnico ejecuta sobre SU solicitud.
    public function accept(User $user, ServiceRequest $sr): bool            { return $this->ownsSr($user, $sr); }
    public function reject(User $user, ServiceRequest $sr): bool            { return $this->ownsSr($user, $sr); }
    public function start(User $user, ServiceRequest $sr): bool             { return $this->ownsSr($user, $sr); }
    public function resolve(User $user, ServiceRequest $sr): bool           { return $this->ownsSr($user, $sr); }
    public function pause(User $user, ServiceRequest $sr): bool             { return $this->ownsSr($user, $sr); }
    public function resume(User $user, ServiceRequest $sr): bool            { return $this->ownsSr($user, $sr); }
    public function close(User $user, ServiceRequest $sr): bool             { return $this->ownsSr($user, $sr); }
    public function reopen(User $user, ServiceRequest $sr): bool            { return $this->ownsSr($user, $sr); }
    public function cancel(User $user, ServiceRequest $sr): bool            { return $this->ownsSr($user, $sr); }
    public function finalizeNonViable(User $user, ServiceRequest $sr): bool { return $this->ownsSr($user, $sr); }
    public function generateResolution(User $user, ServiceRequest $sr): bool { return $this->ownsSr($user, $sr); }
    public function generateEmailReply(User $user, ServiceRequest $sr): bool { return $this->ownsSr($user, $sr); }
    public function updateCut(User $user, ServiceRequest $sr): bool         { return $this->ownsSr($user, $sr); }
    public function closeVencimiento(User $user, ServiceRequest $sr): bool  { return $this->ownsSr($user, $sr); }
    public function downloadReport(User $user, ServiceRequest $sr): bool    { return $this->ownsSr($user, $sr); }

    /**
     * Propiedad de una solicitud para un técnico: exclusivamente assigned_to === user->id.
     * No se usa pertenencia a entidad.
     */
    private function ownsSr(User $user, ServiceRequest $sr): bool
    {
        return (int) $sr->assigned_to === (int) $user->id;
    }

    // Asignación/reasignación entre técnicos = administrativo (solo admin vía before()).
    public function reassign(User $user, ServiceRequest $sr): bool { return false; }
    public function assign(User $user, ServiceRequest $sr): bool   { return false; }

    public function create(User $user): bool
    {
        return true; // técnico puede capturar solicitudes (quedan asignables por admin)
    }

    public function delete(User $user, ServiceRequest $sr): bool
    {
        return false; // solo admin
    }
}
