<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

/**
 * Rank rules for roles (auto-discovered: App\Models\Role -> App\Policies\RolePolicy).
 *
 * Note the deliberate asymmetry with users:
 *   - ASSIGNING a role to someone is allowed at or below your rank (an admin
 *     may appoint another admin) — see UserPolicy::assignRoleLevel().
 *   - EDITING a role's permissions is only allowed strictly below your rank, so
 *     an admin can never rewrite what "Admin" itself means. Only a super_admin can.
 */
class RolePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('roles.view') || $actor->can('roles.manage');
    }

    public function view(User $actor, Role $role): bool
    {
        if (! $this->viewAny($actor)) {
            return false;
        }
        return $actor->isSuperAdmin() || ! $role->isSuperAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->can('roles.manage');
    }

    /** Change a role's permissions / label / description. */
    public function update(User $actor, Role $role): bool
    {
        return $actor->can('roles.manage') && $role->isEditableBy($actor);
    }

    public function delete(User $actor, Role $role): bool
    {
        return $actor->can('roles.manage') && $role->isDeletableBy($actor);
    }
}
