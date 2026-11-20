<?php

namespace App\Services\Access;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class AccessManager
{
    public const ROLE_NAMES = [
        'Super Admin',
        'Administrator',
        'Manager',
        'Accountant',
        'Cashier',
        'Salesperson',
    ];

    public const RESOURCES = [
        'clients', 'products', 'categories', 'expenses', 'sales',
        'invoices', 'payments', 'reports', 'branches', 'users', 'roles', 'permissions', 'audit',
    ];

    public const VERBS = ['view', 'create', 'update', 'delete', 'export'];

    /** Permissions required to administer users, roles and permissions. */
    public const ACCESS_MANAGEMENT_PERMISSIONS = [
        'users.view', 'users.create', 'users.update', 'users.delete',
        'roles.view', 'roles.create', 'roles.update', 'roles.delete',
        'permissions.view', 'permissions.create', 'permissions.update', 'permissions.delete',
    ];

    public function ensureDefaults(): array
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        foreach (self::ROLE_NAMES as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $permissions = [];
        foreach (self::VERBS as $verb) {
            foreach (self::RESOURCES as $resource) {
                $name = $resource . '.' . $verb;
                $permissions[] = Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);
            }
        }

        $all = collect($permissions);

        // Administrator is deliberately a full operational administrator.
        // Super Admin is elevated through Gate::before in AppServiceProvider.
        $administrator = Role::findByName('Administrator', 'web');
        $superAdmin = Role::findByName('Super Admin', 'web');

        $administrator->syncPermissions($all);
        $superAdmin->syncPermissions($all);

        // Repair existing administrator accounts as part of every synchronization.
        // This makes upgrades idempotent and prevents an old role cache/permission
        // assignment from leaving an administrator locked out of access management.
        User::role('Administrator', 'web')->each(function (User $user) use ($all): void {
            $user->syncRoles(['Administrator']);
            $user->syncPermissions($all);
        });

        // Ensure the known bootstrap administrator exists with the full administrator access.
        User::where('email', 'admin@mauzohub.test')->each(function (User $user) use ($all): void {
            $user->syncRoles(['Administrator']);
            $user->syncPermissions($all);
        });

        Role::findByName('Manager', 'web')->syncPermissions(
            $all->filter(fn ($permission) => ! str_ends_with($permission->name, '.delete'))
        );

        Role::findByName('Accountant', 'web')->syncPermissions(
            $all->filter(fn ($permission) => in_array($permission->name, [
                'clients.view', 'clients.create', 'clients.update',
                'invoices.view', 'invoices.create', 'invoices.update',
                'payments.view', 'payments.create', 'payments.update',
                'payments.delete', 'expenses.view', 'expenses.create',
                'expenses.update', 'expenses.delete', 'reports.view', 'reports.export',
            ], true))
        );

        Role::findByName('Cashier', 'web')->syncPermissions(
            $all->filter(fn ($permission) => in_array($permission->name, [
                'clients.view', 'clients.create', 'clients.update',
                'products.view', 'categories.view', 'sales.view', 'sales.create',
                'invoices.view', 'payments.view', 'payments.create',
            ], true))
        );

        Role::findByName('Salesperson', 'web')->syncPermissions(
            $all->filter(fn ($permission) => in_array($permission->name, [
                'clients.view', 'clients.create', 'clients.update',
                'products.view', 'categories.view', 'sales.view', 'sales.create',
                'invoices.view', 'reports.view',
            ], true))
        );

        $registrar->forgetCachedPermissions();

        return [
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->get(),
            'permissions' => $all,
        ];
    }

    public function makeAdministrator(User $user): User
    {
        $this->ensureDefaults();
        $user->syncRoles(['Administrator']);
        return $user->fresh(['roles', 'permissions']);
    }
}
