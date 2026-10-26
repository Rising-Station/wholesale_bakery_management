<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        // Guest attempting to access dashboard is redirected to login
        $guestResponse = $this->get(route('dashboard'));
        $guestResponse->assertRedirect(route('login'));

        // Authenticated staff can access dashboard
        $user = User::factory()->create(['is_active' => true]);
        $authenticatedResponse = $this->actingAs($user)->get(route('dashboard'));
        $authenticatedResponse->assertStatus(200);
    }
}
