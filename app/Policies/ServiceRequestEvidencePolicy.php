<?php

namespace App\Policies;

use App\Models\ServiceRequestEvidence;
use App\Models\User;

class ServiceRequestEvidencePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Propiedad por la SR padre: assigned_to === user->id.
     */
    private function ownsParent(User $user, ServiceRequestEvidence $e): bool
    {
        return (int) $e->serviceRequest?->assigned_to === (int) $user->id;
    }

    public function view(User $user, ServiceRequestEvidence $e): bool
    {
        return $this->ownsParent($user, $e);
    }

    public function delete(User $user, ServiceRequestEvidence $e): bool
    {
        // Borra solo lo que él subió (user_id) y sobre una SR asignada a él.
        return (int) $e->user_id === (int) $user->id && $this->ownsParent($user, $e);
    }

    public function create(User $user): bool
    {
        return true; // la SR destino se valida en el controlador
    }
}
