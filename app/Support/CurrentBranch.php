<?php

namespace App\Support;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The single source of truth for "which branch am I working in?".
 *
 * Users without `branches.view_all` are hard-scoped to their own branch: the
 * session is ignored entirely, so they cannot escape it by any means.
 *
 * Users with `branches.view_all` (admin, super_admin) may switch a session-backed
 * working branch, or select "All branches" (null) which is READ-ONLY — writes call
 * requireId() and are refused until a concrete branch is picked.
 */
class CurrentBranch
{
    public const SESSION_KEY = 'branch_view';

    /** Explicit "All branches" selection, as stored in the session. */
    public const ALL = 'all';

    /**
     * Effective working branch. null = "All branches" (view-only, all-branch users only).
     *
     * With no selection made, an all-branch user starts in their OWN branch rather than
     * All — All is view-only, so defaulting to it would leave a fresh login unable to
     * create anything until they picked a branch.
     */
    public static function id(): ?int
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        // Scoped users are pinned to their own branch — session can't override it.
        if (! $user->seesAllBranches()) {
            return $user->branch_id ? (int) $user->branch_id : null;
        }

        $selected = session(self::SESSION_KEY);

        // No choice yet: fall back to their own branch.
        if ($selected === null) {
            return $user->branch_id ? (int) $user->branch_id : null;
        }

        if ($selected === self::ALL) {
            return null;
        }

        // Stale session (branch deleted since selection) reverts to their own branch.
        if (! self::options()->contains('id', (int) $selected)) {
            session()->forget(self::SESSION_KEY);

            return $user->branch_id ? (int) $user->branch_id : null;
        }

        return (int) $selected;
    }

    /**
     * True when viewing every branch at once (view-only mode).
     */
    public static function isAll(): bool
    {
        return self::id() === null && (bool) auth()->user()?->seesAllBranches();
    }

    /**
     * Branch id for writes. Callers must handle the null return by redirecting
     * back with self::pickBranchMessage() — records need a concrete branch.
     */
    public static function requireId(): ?int
    {
        return self::id();
    }

    public static function pickBranchMessage(): string
    {
        return 'Pick a working branch first — you cannot create records while viewing all branches.';
    }

    /**
     * Constrain a query to the working branch. In All-branches mode this is a
     * no-op, which is exactly what makes cross-branch reads work.
     */
    public static function scope(Builder $query, string $column = 'branch_id'): Builder
    {
        $id = self::id();

        return $id === null ? $query : $query->where($column, $id);
    }

    /**
     * May the current user touch a record belonging to this branch?
     * Always true for all-branch users.
     */
    public static function allows(?int $branchId): bool
    {
        if (auth()->user()?->seesAllBranches()) {
            return true;
        }

        return (int) $branchId === (int) self::id();
    }

    /**
     * Guard an existing record. No-op for all-branch users; 404 on mismatch otherwise
     * (404 not 403 — a scoped user shouldn't learn the record exists).
     */
    public static function guard(?int $branchId): void
    {
        abort_if(! self::allows($branchId), 404);
    }

    /**
     * Branches the current user may work in / switch between.
     */
    public static function options(): Collection
    {
        $user = auth()->user();

        if (! $user) {
            return collect();
        }

        if ($user->seesAllBranches()) {
            return Branch::orderBy('name')->get(['id', 'name']);
        }

        return Branch::where('id', $user->branch_id)->orderBy('name')->get(['id', 'name']);
    }

    public static function name(): string
    {
        $id = self::id();

        if ($id === null) {
            return 'All branches';
        }

        return self::options()->firstWhere('id', $id)->name ?? 'Branch';
    }

    /**
     * Switch the working branch. Only all-branch users may do this; null = All branches.
     */
    public static function set(?int $branchId): bool
    {
        if (! auth()->user()?->seesAllBranches()) {
            return false;
        }

        // null means an explicit "All branches" choice — stored, not forgotten, so it
        // isn't mistaken for "no choice yet" (which falls back to their own branch).
        if ($branchId === null) {
            session([self::SESSION_KEY => self::ALL]);

            return true;
        }

        if (! self::options()->contains('id', $branchId)) {
            return false;
        }

        session([self::SESSION_KEY => $branchId]);

        return true;
    }
}
