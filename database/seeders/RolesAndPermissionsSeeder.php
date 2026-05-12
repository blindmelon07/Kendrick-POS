<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles/permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Permissions ───────────────────────────────────────────────────────
        $permissions = [
            // POS
            'pos.access',
            'pos.void',

            // Inventory
            'inventory.view',
            'inventory.manage',
            'inventory.stock',

            // Deliveries
            'deliveries.view',
            'deliveries.manage',

            // Reports / Dashboard
            'dashboard.view',
            'reports.view',

            // Users
            'users.manage',

            // Rider
            'rider.access',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ── Roles ─────────────────────────────────────────────────────────────

        /** Cashier: only POS terminal */
        $cashier = Role::firstOrCreate(['name' => 'cashier']);
        $cashier->syncPermissions([
            'pos.access',
            'dashboard.view',
        ]);

        /** Manager: inventory + deliveries, can also use POS */
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'pos.access',
            'pos.void',
            'inventory.view',
            'inventory.manage',
            'inventory.stock',
            'deliveries.view',
            'deliveries.manage',
            'dashboard.view',
            'reports.view',
        ]);

        /** Admin: full access */
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        /** Rider: delivery portal only */
        $rider = Role::firstOrCreate(['name' => 'rider']);
        $rider->syncPermissions(['rider.access']);

        // ── Demo Users ────────────────────────────────────────────────────────
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Admin User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $adminUser->syncRoles('admin');

        $managerUser = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name'     => 'Manager User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $managerUser->syncRoles('manager');

        $cashierUser = User::firstOrCreate(
            ['email' => 'cashier@example.com'],
            [
                'name'     => 'Cashier User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $cashierUser->syncRoles('cashier');

        $riderUser = User::firstOrCreate(
            ['email' => 'rider@example.com'],
            [
                'name'     => 'Rider User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $riderUser->syncRoles('rider');

        $this->command->info('Roles: admin, manager, cashier, rider — and 4 demo users created.');
    }
}

