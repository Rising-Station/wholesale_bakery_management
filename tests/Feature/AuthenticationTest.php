<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
    }

    public function test_login_screen_provides_demo_login_enabled_true_by_default(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
            ->where('demoLoginEnabled', true)
        );
    }

    public function test_login_screen_respects_disabled_demo_login_setting(): void
    {
        Setting::set('enable_demo_login', '0', 'general');

        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
            ->where('demoLoginEnabled', false)
        );
    }

    public function test_users_can_authenticate_using_email(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@mominulbekari.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $response = $this->post(route('login'), [
            'login' => 'staff@mominulbekari.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_can_authenticate_using_phone(): void
    {
        $user = User::factory()->create([
            'email' => 'driver@mominulbekari.com',
            'phone' => '01711998877',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $response = $this->post(route('login'), [
            'login' => '01711998877',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@mominulbekari.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $response = $this->post(route('login'), [
            'login' => 'staff@mominulbekari.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'terminated@mominulbekari.com',
            'password' => Hash::make('password123'),
            'is_active' => false, // Deactivated
        ]);

        $response = $this->post(route('login'), [
            'login' => 'terminated@mominulbekari.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_active_user_middleware_logs_out_deactivated_users(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }
}
