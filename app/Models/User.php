<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'branch_id',
        'counter_id',
        'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }
    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    // ── Role hierarchy ────────────────────────────────────────────────────
    // Everything below keys off the role's `level` (rank). See App\Models\Role.

    /** This user's rank = the highest level among their roles (0 if none). */
    public function level(): int
    {
        return (int) ($this->roles->max('level') ?? 0);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * May this user manage (edit / deactivate / set roles on) another account?
     * Rule: accounts ranked at or below your own — so an admin can appoint a
     * peer admin. super_admin accounts stay invisible/untouchable to everyone
     * else, and nobody edits themselves through the users screen.
     */
    public function canManage(User $other): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        if ($other->isSuperAdmin() || $this->is($other)) {
            return false;
        }
        return $other->level() <= $this->level();
    }

    /** Admin/super_admin see every branch; everyone else is scoped to their own. */
    public function seesAllBranches(): bool
    {
        return $this->can('branches.view_all');
    }
}
