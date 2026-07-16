<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles & permissions screen.
 *
 * Three rules are enforced here, all server-side:
 *   1. Rank      — you only see/edit roles below your own (super_admin bypasses).
 *   2. Invisible — the super_admin role never appears for anyone else.
 *   3. No escalation — you can only tick permissions you personally hold, so
 *      nobody can grant away more power than they have.
 */
class RoleController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Role::class);

        $actor = auth()->user();

        $roles = Role::visibleTo($actor)
            ->withCount('permissions')
            ->orderByDesc('level')
            ->get();

        return view('roles.index', [
            'roles'  => $roles,
            'groups' => $this->groupsFor($actor),
            'actor'  => $actor,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Role::class);

        $actor = auth()->user();

        $data = $request->validate([
            'label'       => 'required|string|max:100',
            'level'       => 'required|integer|min:1|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        // A new role must sit strictly below you — you can't mint a peer/superior.
        abort_if(
            ! $actor->isSuperAdmin() && $data['level'] >= $actor->level(),
            403,
            'You can only create roles ranked below your own.'
        );

        $name = $this->uniqueName($data['label']);

        $role = Role::create([
            'name'        => $name,
            'guard_name'  => 'web',
            'label'       => $data['label'],
            'level'       => $data['level'],
            'is_system'   => false,
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->route('roles.index')
                         ->with('success', "Role \"{$role->displayName()}\" created. Now choose what it can do.");
    }

    /** Save a role's permission matrix (and its label/description). */
    public function update(Request $request, Role $role)
    {
        $this->authorize('update', $role);

        $actor = auth()->user();

        $request->validate([
            'label'         => ['nullable', 'string', 'max:100'],
            'description'   => ['nullable', 'string', 'max:255'],
            'permissions'   => ['array'],
            'permissions.*' => ['string'],
        ]);

        // Only permissions the actor personally holds may be toggled. Anything
        // outside that set keeps whatever the role already had — so an admin
        // editing a role can never add (or silently strip) developer.* etc.
        $allowed  = $this->allowedPermissionNames($actor);
        $incoming = array_values(array_intersect($request->input('permissions', []), $allowed));
        $retained = $role->permissions->pluck('name')->diff($allowed)->values()->all();

        $role->syncPermissions(array_merge($incoming, $retained));

        if (! $role->is_system) {
            $role->update([
                'label'       => $request->input('label') ?: $role->label,
                'description' => $request->input('description'),
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('roles.index')
                         ->with('success', "Permissions saved for \"{$role->displayName()}\".");
    }

    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);

        if ($role->users()->count() > 0) {
            return back()->with('error', "\"{$role->displayName()}\" still has users. Move them to another role first.");
        }

        $name = $role->displayName();
        $role->delete();

        return redirect()->route('roles.index')->with('success', "Role \"{$name}\" deleted.");
    }

    // ── helpers ───────────────────────────────────────────────────────────

    /**
     * The catalogue, filtered to what this actor may hand out.
     * Developer-only groups are hidden from everyone but super_admin.
     */
    private function groupsFor($actor): array
    {
        $allowed = $this->allowedPermissionNames($actor);
        $out     = [];

        foreach (config('permissions', []) as $key => $group) {
            if (! empty($group['developer']) && ! $actor->isSuperAdmin()) {
                continue;
            }
            $perms = array_filter(
                $group['permissions'] ?? [],
                fn ($name) => in_array($name, $allowed, true),
                ARRAY_FILTER_USE_KEY
            );
            if ($perms) {
                $out[$key] = ['label' => $group['label'] ?? ucfirst($key), 'permissions' => $perms];
            }
        }

        return $out;
    }

    /** Permission names the actor personally holds (super_admin holds all). */
    private function allowedPermissionNames($actor): array
    {
        if ($actor->isSuperAdmin()) {
            return Permission::pluck('name')->all();
        }
        return $actor->getAllPermissions()->pluck('name')->all();
    }

    /** slug from the label, kept unique (roles.name is the stable key). */
    private function uniqueName(string $label): string
    {
        $base = \Illuminate\Support\Str::slug($label, '_') ?: 'role';
        $name = $base;
        $i    = 2;
        while (Role::where('name', $name)->exists()) {
            $name = $base . '_' . $i++;
        }
        return $name;
    }
}
