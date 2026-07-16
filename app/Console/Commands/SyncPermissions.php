<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates any permission listed in config/permissions.php that doesn't exist yet.
 *
 * Deliberately create-only: it never deletes or prunes, so permissions granted by
 * hand in the Roles screen always survive a sync.
 */
class SyncPermissions extends Command
{
    protected $signature   = 'permissions:sync';
    protected $description = 'Create any missing permissions from config/permissions.php';

    public function handle(): int
    {
        $created = 0;
        $total   = 0;

        foreach (config('permissions', []) as $group) {
            foreach (array_keys($group['permissions'] ?? []) as $name) {
                $total++;
                $perm = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
                if ($perm->wasRecentlyCreated) {
                    $created++;
                    $this->line("  + {$name}");
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Permissions synced: {$created} created, {$total} in catalogue.");

        return self::SUCCESS;
    }
}
