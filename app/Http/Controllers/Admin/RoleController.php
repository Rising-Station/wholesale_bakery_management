<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of roles and permissions.
     */
    public function index(): Response
    {
        $roles = Role::with(['permissions', 'users:id,name'])->get()->map(function (Role $role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $role->users->count(),
                'permissions' => $role->permissions->pluck('name'),
                'is_protected' => in_array($role->name, ['SuperAdmin']),
            ];
        });

        // Group permissions by category for the matrix
        $permissions = Permission::all()->groupBy(function (Permission $permission) {
            $parts = explode('.', $permission->name);

            return ucfirst($parts[0]);
        })->map(function ($items, $group) {
            return [
                'group' => $group,
                'permissions' => $items->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'label' => ucwords(str_replace(['.', '_'], ' ', $p->name)),
                ])->values(),
            ];
        })->values();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'permissionsByGroup' => $permissions,
        ]);
    }

    /**
     * Store a newly created role with assigned permissions.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->validated('permissions'));
        }

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' created successfully.");
    }

    /**
     * Update the specified role and its permissions.
     */
    public function update(StoreRoleRequest $request, Role $role): RedirectResponse
    {
        if ($role->name === 'SuperAdmin') {
            return redirect()->route('roles.index')->with('error', 'SuperAdmin role cannot be modified.');
        }

        $role->update([
            'name' => $request->validated('name'),
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' updated successfully.");
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->name, ['SuperAdmin', 'BranchManager', 'Accountant', 'SalesDriver', 'InventoryStorekeeper'], true)) {
            return redirect()->route('roles.index')->with('error', 'Standard system roles cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')->with('error', 'Cannot delete role assigned to active users.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }
}
