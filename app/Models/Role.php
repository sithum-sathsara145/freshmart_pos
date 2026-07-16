<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Roles carry a `level` (rank). The rule everywhere is the same: you may only
 * see/assign/edit roles ranked strictly BELOW your own highest role.
 *
 *   super_admin 100  (developer-only, invisible to everyone else)
 *   admin        90  (everything except developer.*)
 *   manager      60  (the branch manager)
 *   stock_manager40
 *   cashier      20
 *
 * `is_system` roles (super_admin, admin) cannot be renamed or deleted.
 */
class Role extends SpatieRole
{
    // Spatie's Role has $guarded = [], so level/label/is_system/description
    // are mass-assignable without redeclaring $fillable.

    protected $casts = [
        'level'     => 'integer',
        'is_system' => 'boolean',
    ];

    public const SUPER_ADMIN = 'super_admin';
    public const ADMIN       = 'admin';

    /** Display name for the UI, falling back to a prettified name. */
    public function displayName(): string
    {
        return $this->label ?: ucwords(str_replace('_', ' ', $this->name));
    }

    public function isSuperAdmin(): bool
    {
        return $this->name === self::SUPER_ADMIN;
    }

    /**
     * Roles this user is allowed to SEE.
     * super_admin is invisible to everyone who isn't super_admin.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user && $user->isSuperAdmin()) {
            return $query;
        }
        return $query->where('name', '!=', self::SUPER_ADMIN);
    }

    /**
     * Roles this user may ASSIGN to a user account — at or below their own rank,
     * so an admin can appoint another admin. super_admin is never assignable by
     * anyone but a super_admin.
     */
    public function scopeAssignableBy(Builder $query, ?User $user): Builder
    {
        if ($user && $user->isSuperAdmin()) {
            return $query;
        }
        return $query->visibleTo($user)
                     ->where('level', '<=', $user?->level() ?? 0);
    }

    /**
     * Roles whose PERMISSIONS this user may edit (or rename/delete) — strictly
     * below their own rank. This is deliberately stricter than assigning: an
     * admin may appoint another admin, but may not rewrite what "Admin" means.
     * Only a super_admin can do that.
     */
    public function scopeEditableBy(Builder $query, ?User $user): Builder
    {
        if ($user && $user->isSuperAdmin()) {
            return $query;
        }
        return $query->visibleTo($user)
                     ->where('level', '<', $user?->level() ?? 0);
    }

    /** May this user change this role's permissions / rename / delete it? */
    public function isEditableBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        return ! $this->isSuperAdmin() && $this->level < $user->level();
    }

    /** System roles keep their name and can't be deleted, even by a super_admin. */
    public function isDeletableBy(?User $user): bool
    {
        return ! $this->is_system && $this->isEditableBy($user);
    }
}
