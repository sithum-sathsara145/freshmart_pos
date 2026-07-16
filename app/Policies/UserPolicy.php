<?php

namespace App\Policies;

use App\Models\User;

/**
 * The rank rules for user accounts live here (auto-discovered by Laravel:
 * App\Models\User -> App\Policies\UserPolicy).
 *
 * Two ideas do all the work:
 *   - `users.manage` says you may use the Users screen at all.
 *   - User::canManage() says WHICH accounts you may touch (at or below your
 *     rank; never a super_admin; never yourself).
 *
 * Granting `users.manage` to, say, `manager` is therefore all it takes to
 * delegate: the rank rule automatically confines them to accounts at/below
 * level 60.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('users.view') || $actor->can('users.manage');
    }

    public function view(User $actor, User $target): bool
    {
        if (! $this->viewAny($actor)) {
            return false;
        }
        // super_admin accounts are invisible to everyone else.
        return $actor->isSuperAdmin() || ! $target->isSuperAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->can('users.manage');
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->can('users.manage') && $actor->canManage($target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->can('users.manage') && $actor->canManage($target);
    }

    /** May the actor put `$roleLevel` on an account? (no privilege escalation) */
    public function assignRoleLevel(User $actor, int $roleLevel): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }
        return $actor->can('users.manage') && $roleLevel <= $actor->level();
    }
}
