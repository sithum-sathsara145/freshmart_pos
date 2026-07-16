<?php

namespace App\Providers;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // super_admin implicitly passes every gate/permission check. This is the
        // developer escape hatch: even if a permission is missing or a route is
        // mis-gated, a super_admin can always get in and fix it.
        //
        // Returning null (not false) for everyone else lets the normal checks run.
        Gate::before(function ($user, $ability) {
            return $user->hasRole(Role::SUPER_ADMIN) ? true : null;
        });

        // whereBranch(null) means "every branch", NOT "branch_id IS NULL".
        // Plain where('branch_id', null) would silently return zero rows, so every
        // branch filter goes through this macro instead.
        $whereBranch = function (?int $branchId, string $column = 'branch_id') {
            /** @var EloquentBuilder|QueryBuilder $this */
            return $branchId === null ? $this : $this->where($column, $branchId);
        };

        EloquentBuilder::macro('whereBranch', $whereBranch);
        QueryBuilder::macro('whereBranch', $whereBranch);
    }
}
