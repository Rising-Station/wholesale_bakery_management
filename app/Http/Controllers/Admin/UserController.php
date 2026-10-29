<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $roleFilter = $request->input('role');

        $users = User::with('roles')
            ->when($search, function ($query, $search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($roleFilter, function ($query, $roleFilter): void {
                $query->whereHas('roles', function ($q) use ($roleFilter): void {
                    $q->where('name', $roleFilter);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'is_active' => $user->is_active,
                    'roles' => $user->roles->pluck('name'),
                    'role' => $user->roles->first()?->name,
                    'created_at' => $user->created_at?->format('Y-m-d H:i'),
                ];
            });

        $roles = Role::pluck('name');

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
            ],
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'password' => Hash::make($request->validated('password')),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $user->assignRole($request->validated('role'));

        return redirect()->route('users.index')->with('success', "User '{$user->name}' created successfully.");
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        $user->update($data);
        $user->syncRoles([$request->validated('role')]);

        return redirect()->route('users.index')->with('success', "User '{$user->name}' updated successfully.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        if (Auth::id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }

        if ($user->hasRole('SuperAdmin') && User::role('SuperAdmin')->count() <= 1) {
            return redirect()->route('users.index')->with('error', 'Cannot delete the sole SuperAdmin account.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Toggle user active/inactive status.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        if (Auth::id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $statusStr = $user->is_active ? 'activated' : 'deactivated';

        return redirect()->route('users.index')->with('success', "User '{$user->name}' {$statusStr}.");
    }
}
