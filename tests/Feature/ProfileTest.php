<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_profile_page_is_displayed_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'name' => 'Kader Mia',
            'email' => 'kader@mominulbekari.com',
            'phone' => '01711000999',
        ]);
        $user->assignRole('BranchManager');

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Edit')
            ->has('user')
            ->where('user.name', 'Kader Mia')
            ->where('user.email', 'kader@mominulbekari.com')
            ->where('user.phone', '01711000999')
            ->where('user.role', 'BranchManager')
        );
    }

    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $response = $this->get(route('profile.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'name' => 'Old Name',
            'email' => 'old@mominulbekari.com',
            'phone' => '01711111111',
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'New Name Updated',
            'email' => 'new@mominulbekari.com',
            'phone' => '01711222222',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('New Name Updated', $user->name);
        $this->assertSame('new@mominulbekari.com', $user->email);
        $this->assertSame('01711222222', $user->phone);
    }

    public function test_profile_update_validates_email_uniqueness(): void
    {
        User::factory()->create([
            'email' => 'existing@mominulbekari.com',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'email' => 'me@mominulbekari.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'My New Name',
            'email' => 'existing@mominulbekari.com',
            'phone' => '01711000000',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_update_password_with_correct_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password-123'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'old-password-123',
            'password' => 'new-secure-password-456',
            'password_confirmation' => 'new-secure-password-456',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-secure-password-456', $user->fresh()->password));
    }

    public function test_user_cannot_update_password_with_incorrect_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('actual-password-123'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'wrong-password',
            'password' => 'new-secure-password-456',
            'password_confirmation' => 'new-secure-password-456',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('actual-password-123', $user->fresh()->password));
    }
}

