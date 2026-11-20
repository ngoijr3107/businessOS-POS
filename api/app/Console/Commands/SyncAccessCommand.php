<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\User;
use App\Services\Access\AccessManager;
use Illuminate\Console\Command;

class SyncAccessCommand extends Command
{
    protected $signature = 'mauzohub:access-sync
        {--admin-email=admin@mauzohub.test : Email of the administrator account to repair}
        {--all-users : Reapply the default Administrator role permissions to all Administrator users}';

    protected $description = 'Create/synchronize MauzoHub roles and permissions and repair administrator access';

    public function handle(AccessManager $access): int
    {
        $access->ensureDefaults();

        $email = $this->option('admin-email');
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->syncRoles(['Administrator']);
            $user->syncPermissions(Permission::where('guard_name', 'web')->get());
            $this->info("Administrator access repaired for {$user->email}.");
        } else {
            $this->warn("Administrator account {$email} was not found; roles and permissions were still synchronized.");
        }

        if ($this->option('all-users')) {
            User::role('Administrator', 'web')->each(function (User $administrator) {
                $administrator->syncRoles(['Administrator']);
                $administrator->syncPermissions(Permission::where('guard_name', 'web')->get());
            });
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            $this->info('All Administrator users were synchronized.');
        }

        $this->table(
            ['Role', 'Permissions'],
            \App\Models\Role::where('guard_name', 'web')
                ->withCount('permissions')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($role) => [$role->name, $role->permissions_count])
                ->all()
        );

        $this->info('Access synchronization completed successfully.');
        return self::SUCCESS;
    }
}
