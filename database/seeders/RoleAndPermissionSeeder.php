<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionsByModule = [
            'Users' => ['users.view', 'users.create', 'users.edit', 'users.delete'],
            'Roles' => ['roles.view', 'roles.manage'],
            'Suppliers' => ['suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete', 'suppliers.payment'],
            'Purchases' => ['purchases.view', 'purchases.create', 'purchases.edit', 'purchases.delete', 'purchases.receive'],
            'Warehouse & Stock' => ['stock.view', 'stock.adjust', 'stock.scrap'],
            'Products' => ['products.view', 'products.create', 'products.edit', 'products.delete'],
            'Roads' => ['roads.view', 'roads.create', 'roads.edit', 'roads.delete'],
            'Retailers' => ['retailers.view', 'retailers.create', 'retailers.edit', 'retailers.delete', 'retailers.payment'],
            'Vehicle Trips' => ['trips.view', 'trips.create', 'trips.dispatch', 'trips.reconcile'],
            'Sales Memos' => ['memos.view', 'memos.create', 'memos.edit', 'memos.cancel', 'memos.print'],
            'Damage & Returns' => ['damage.view', 'damage.create', 'damage.claim'],
            'Expenses' => ['expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete'],
            'Reports & Settings' => ['reports.view', 'settings.manage', 'backup.manage'],
        ];

        $allPermissions = [];
        foreach ($permissionsByModule as $module => $permissions) {
            foreach ($permissions as $permissionName) {
                $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
                $allPermissions[] = $permission;
            }
        }

        // 1. SuperAdmin Role
        $superAdminRole = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(Permission::all());

        // 2. BranchManager Role
        $branchManagerRole = Role::firstOrCreate(['name' => 'BranchManager', 'guard_name' => 'web']);
        $branchManagerRole->syncPermissions([
            'users.view',
            'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.payment',
            'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.receive',
            'stock.view', 'stock.adjust', 'stock.scrap',
            'products.view', 'products.create', 'products.edit',
            'roads.view', 'roads.create', 'roads.edit',
            'retailers.view', 'retailers.create', 'retailers.edit', 'retailers.payment',
            'trips.view', 'trips.create', 'trips.dispatch', 'trips.reconcile',
            'memos.view', 'memos.create', 'memos.edit', 'memos.print',
            'damage.view', 'damage.create', 'damage.claim',
            'expenses.view', 'expenses.create', 'expenses.edit',
            'reports.view',
        ]);

        // 3. Accountant Role
        $accountantRole = Role::firstOrCreate(['name' => 'Accountant', 'guard_name' => 'web']);
        $accountantRole->syncPermissions([
            'suppliers.view', 'suppliers.payment',
            'purchases.view',
            'retailers.view', 'retailers.payment',
            'memos.view', 'memos.print',
            'trips.view', 'trips.reconcile',
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete',
            'reports.view',
        ]);

        // 4. SalesDriver Role
        $salesDriverRole = Role::firstOrCreate(['name' => 'SalesDriver', 'guard_name' => 'web']);
        $salesDriverRole->syncPermissions([
            'roads.view',
            'retailers.view', 'retailers.create', 'retailers.payment',
            'trips.view',
            'memos.view', 'memos.create', 'memos.print',
            'damage.view', 'damage.create',
        ]);

        // 5. InventoryStorekeeper Role
        $storekeeperRole = Role::firstOrCreate(['name' => 'InventoryStorekeeper', 'guard_name' => 'web']);
        $storekeeperRole->syncPermissions([
            'stock.view', 'stock.adjust', 'stock.scrap',
            'purchases.view', 'purchases.receive',
            'trips.view', 'trips.create',
            'products.view',
            'damage.view',
        ]);

        // Create Default SuperAdmin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@mominulbekari.com'],
            [
                'name' => 'Mominul Haque (Admin)',
                'phone' => '01711000001',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );
        $admin->syncRoles([$superAdminRole]);

        // Create Sample Branch Manager
        $manager = User::firstOrCreate(
            ['email' => 'manager@mominulbekari.com'],
            [
                'name' => 'Tariqul Islam (Depot Manager)',
                'phone' => '01711000002',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );
        $manager->syncRoles([$branchManagerRole]);

        // Create Sample Accountant
        $accountant = User::firstOrCreate(
            ['email' => 'accounts@mominulbekari.com'],
            [
                'name' => 'Kamrul Hasan (Accountant)',
                'phone' => '01711000003',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );
        $accountant->syncRoles([$accountantRole]);

        // Create Sample Sales Driver for Road A
        $driver = User::firstOrCreate(
            ['email' => 'driver.a@mominulbekari.com'],
            [
                'name' => 'Rafiqul Islam (Road A Driver)',
                'phone' => '01711000004',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );
        $driver->syncRoles([$salesDriverRole]);

        // Create Sample Storekeeper
        $storekeeper = User::firstOrCreate(
            ['email' => 'store@mominulbekari.com'],
            [
                'name' => 'Jashim Uddin (Storekeeper)',
                'phone' => '01711000005',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );
        $storekeeper->syncRoles([$storekeeperRole]);
    }
}
