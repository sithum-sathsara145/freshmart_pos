<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * User accounts. The list itself is rendered by the Settings → Users tab
 * (SettingController@index); this handles the writes.
 *
 * Every write re-checks the rank rules server-side — the UI only ever hides
 * things, it never enforces them.
 */
class UserController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $data = $this->validated($request);
        $role = $this->resolveAssignableRole($request);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'phone'     => $data['phone'] ?? null,
            'branch_id' => $this->resolveBranchId($request),
            'counter_id'=> $data['counter_id'] ?? null,
            'status'    => $data['status'] ?? 'active',
        ]);

        $user->syncRoles([$role->name]);

        return redirect()->to(route('settings.index') . '#users')
                         ->with('success', "User \"{$user->name}\" created.");
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $data = $this->validated($request, $user);
        $role = $this->resolveAssignableRole($request);

        $user->update([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'branch_id' => $this->resolveBranchId($request, $user),
            'counter_id'=> $data['counter_id'] ?? null,
            'status'    => $data['status'] ?? 'active',
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        $user->syncRoles([$role->name]);

        return redirect()->to(route('settings.index') . '#users')
                         ->with('success', "User \"{$user->name}\" updated.");
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        // Don't strand records that point at this user — deactivate instead of
        // deleting when they've done anything in the system.
        $hasHistory = \App\Models\Sale::where('user_id', $user->id)->exists()
                   || \App\Models\Purchase::where('user_id', $user->id)->exists()
                   || $user->staff()->exists();

        if ($hasHistory) {
            $user->update(['status' => 'inactive']);
            return back()->with('success', "\"{$user->name}\" has history, so they were deactivated instead of deleted.");
        }

        $name = $user->name;
        $user->delete();

        return redirect()->to(route('settings.index') . '#users')
                         ->with('success', "User \"{$name}\" deleted.");
    }

    // ── helpers ───────────────────────────────────────────────────────────

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password'   => $user ? 'nullable|string|min:6' : 'required|string|min:6',
            'phone'      => 'nullable|string|max:30',
            'branch_id'  => 'nullable|exists:branches,id',
            'counter_id' => 'nullable|exists:counters,id',
            'role'       => 'required|string|exists:roles,name',
            'status'     => 'nullable|in:active,inactive',
        ]);
    }

    /**
     * The chosen role must be one the actor is actually allowed to hand out —
     * this is what stops someone granting themselves a rank above their own.
     */
    private function resolveAssignableRole(Request $request): Role
    {
        // assignableBy() already restricts to roles at/below the actor's rank and
        // hides super_admin — so not finding the role here IS the refusal.
        $role = Role::assignableBy(auth()->user())
                    ->where('name', $request->input('role'))
                    ->first();

        abort_unless($role, 403, 'You cannot assign that role.');

        return $role;
    }

    /** Users who can't see all branches may only place accounts in their own. */
    private function resolveBranchId(Request $request, ?User $user = null): ?int
    {
        $actor = auth()->user();

        if (! $actor->seesAllBranches()) {
            return $actor->branch_id;
        }

        return $request->filled('branch_id') ? (int) $request->input('branch_id') : $user?->branch_id;
    }
}
