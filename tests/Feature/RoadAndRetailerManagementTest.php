<?php

namespace Tests\Feature;

use App\Models\RetailerShop;
use App\Models\Road;
use Database\Seeders\RoadAndRetailerSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoadAndRetailerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(RoadAndRetailerSeeder::class);
    }

    public function test_roads_index_renders_successfully(): void
    {
        $response = $this->get(route('roads.index'));
        $response->assertStatus(200);
    }

    public function test_new_road_can_be_created(): void
    {
        $response = $this->post(route('roads.store'), [
            'name' => 'Road D (South Market Extension)',
            'code' => 'RD-D',
            'vehicle_number' => 'DHK-METRO-22-9900',
            'driver_name' => 'Shafiqul Islam',
            'driver_phone' => '01700998877',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('roads.index'));
        $this->assertDatabaseHas('roads', ['code' => 'RD-D']);
    }

    public function test_road_can_be_updated(): void
    {
        $road = Road::where('code', 'RD-A')->first();

        $response = $this->put(route('roads.update', $road), [
            'name' => 'Road A (Main Commercial Road - Updated)',
            'code' => 'RD-A',
            'vehicle_number' => 'DHK-METRO-11-2035',
            'driver_name' => 'Rafiqul Islam',
            'driver_phone' => '01711000004',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('roads.index'));
        $this->assertDatabaseHas('roads', [
            'id' => $road->id,
            'vehicle_number' => 'DHK-METRO-11-2035',
        ]);
    }

    public function test_retailers_index_renders_with_filters(): void
    {
        $road = Road::where('code', 'RD-A')->first();

        $response = $this->get(route('retailers.index', ['road_id' => $road->id]));
        $response->assertStatus(200);
    }

    public function test_new_retailer_can_be_created_with_opening_due(): void
    {
        $road = Road::where('code', 'RD-B')->first();

        $response = $this->post(route('retailers.store'), [
            'road_id' => $road->id,
            'shop_name' => 'Madina Confectionery',
            'owner_name' => 'Md. Farhad',
            'phone' => '01755112233',
            'address' => 'Station Road #4',
            'credit_limit' => 15000,
            'opening_due' => 3500,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('retailers.index'));
        $this->assertDatabaseHas('retailer_shops', [
            'shop_name' => 'Madina Confectionery',
            'opening_due' => 3500.00,
            'current_due' => 3500.00,
        ]);
    }

    public function test_retailer_can_be_updated(): void
    {
        $retailer = RetailerShop::first();

        $response = $this->put(route('retailers.update', $retailer), [
            'road_id' => $retailer->road_id,
            'shop_name' => 'Al-Madina Mega Store',
            'owner_name' => $retailer->owner_name,
            'phone' => $retailer->phone,
            'address' => 'Updated Address',
            'credit_limit' => 30000,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('retailers.index'));
        $this->assertDatabaseHas('retailer_shops', [
            'id' => $retailer->id,
            'shop_name' => 'Al-Madina Mega Store',
            'credit_limit' => 30000.00,
        ]);
    }

    public function test_retailer_profile_and_ledger_renders(): void
    {
        $retailer = RetailerShop::first();

        $response = $this->get(route('retailers.show', $retailer));
        $response->assertStatus(200);
    }

    public function test_retailer_can_be_deleted(): void
    {
        $retailer = RetailerShop::first();

        $response = $this->delete(route('retailers.destroy', $retailer));
        $response->assertRedirect(route('retailers.index'));

        $this->assertDatabaseMissing('retailer_shops', ['id' => $retailer->id]);
    }

    public function test_road_with_retailers_cannot_be_deleted(): void
    {
        $road = Road::where('code', 'RD-A')->first();

        $response = $this->delete(route('roads.destroy', $road));
        $response->assertRedirect(route('roads.index'));

        // Road must still exist because it has registered shops
        $this->assertDatabaseHas('roads', ['id' => $road->id]);
    }
}
