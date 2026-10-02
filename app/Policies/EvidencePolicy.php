<?php

namespace App\Policies;

use App\Models\Evidence;
use App\Models\User;

class EvidencePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    // No existe cadena de propiedad Evidence -> trabajo del técnico (Requirement
    // no ancla a assigned_to). Fail-closed: el técnico NO accede a evidencia de
    // Requirements. Admin pasa por before().
    public function view(User $user, Evidence $evidence): bool   { return false; }
    public function delete(User $user, Evidence $evidence): bool { return false; }
    public function create(User $user): bool                     { return false; }
}
