<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAndRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('SuperAdmin');
        $this->actingAs($admin);
    }

    public function test_roles_page_can_be_rendered(): void
    {
        $response = $this->get(route('roles.index'));
        $response->assertStatus(200);
    }

    public function test_new_role_can_be_created(): void
    {
        $response = $this->post(route('roles.store'), [
            'name' => 'CustomAuditor',
            'permissions' => ['stock.view', 'reports.view'],
        ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', ['name' => 'CustomAuditor']);

        $role = Role::findByName('CustomAuditor');
        $this->assertTrue($role->hasPermissionTo('stock.view'));
    }

    public function test_role_can_be_updated(): void
    {
        $role = Role::create(['name' => 'Auditor', 'guard_name' => 'web']);

        $response = $this->put(route('roles.update', $role), [
            'name' => 'SeniorAuditor',
            'permissions' => ['stock.view'],
        ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', ['name' => 'SeniorAuditor']);
    }

    public function test_users_page_can_be_rendered(): void
    {
        $response = $this->get(route('users.index'));
        $response->assertStatus(200);
    }

    public function test_new_user_can_be_created_with_role(): void
    {
        $response = $this->post(route('users.store'), [
            'name' => 'New Driver',
            'email' => 'driver.new@mominulbekari.com',
            'phone' => '01811223344',
            'password' => 'secret1234',
            'role' => 'SalesDriver',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'driver.new@mominulbekari.com']);

        $user = User::where('email', 'driver.new@mominulbekari.com')->first();
        $this->assertTrue($user->hasRole('SalesDriver'));
        $this->assertTrue($user->is_active);
    }

    public function test_user_status_can_be_toggled(): void
    {
        $user = User::where('email', 'driver.a@mominulbekari.com')->first();
        $this->assertTrue($user->is_active);

        $response = $this->patch(route('users.toggle-status', $user));
        $response->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertFalse($user->is_active);
    }

    public function test_user_can_be_deleted(): void
    {
        $user = User::where('email', 'driver.a@mominulbekari.com')->first();

        $response = $this->delete(route('users.destroy', $user));
        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_unauthorized_user_is_forbidden_from_viewing_roles(): void
    {
        $driver = User::factory()->create(['is_active' => true]);
        $driver->assignRole('SalesDriver');

        $response = $this->actingAs($driver)->get(route('roles.index'));
        $response->assertForbidden();
    }

    public function test_unauthorized_user_is_forbidden_from_managing_roles(): void
    {
        $driver = User::factory()->create(['is_active' => true]);
        $driver->assignRole('SalesDriver');

        $response = $this->actingAs($driver)->post(route('roles.store'), [
            'name' => 'HackerRole',
            'permissions' => ['stock.view'],
        ]);
        $response->assertForbidden();
    }

    public function test_unauthorized_user_is_forbidden_from_managing_users(): void
    {
        $driver = User::factory()->create(['is_active' => true]);
        $driver->assignRole('SalesDriver');

        $response = $this->actingAs($driver)->get(route('users.index'));
        $response->assertForbidden();

        $response = $this->actingAs($driver)->post(route('users.store'), [
            'name' => 'Fake User',
            'email' => 'fake@mominulbekari.com',
            'password' => 'secret1234',
            'role' => 'SuperAdmin',
        ]);
        $response->assertForbidden();
    }

    public function test_branch_manager_can_view_users_but_cannot_access_roles(): void
    {
        $manager = User::factory()->create(['is_active' => true]);
        $manager->assignRole('BranchManager');

        // BranchManager has users.view permission
        $response = $this->actingAs($manager)->get(route('users.index'));
        $response->assertOk();

        // BranchManager does not have roles.view permission
        $response = $this->actingAs($manager)->get(route('roles.index'));
        $response->assertForbidden();

        // BranchManager does not have users.create permission
        $response = $this->actingAs($manager)->post(route('users.store'), [
            'name' => 'New User by Manager',
            'email' => 'newby@mominulbekari.com',
            'password' => 'secret1234',
            'role' => 'SalesDriver',
        ]);
        $response->assertForbidden();
    }
}
