<?php

namespace Tests\Feature;

use App\Enums\PackagingType;
use App\Enums\StockMovementType;
use App\Enums\TripStatus;
use App\Models\Product;
use App\Models\Road;
use App\Models\User;
use App\Models\VehicleTrip;
use Database\Seeders\RoadAndRetailerSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTripManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(RoadAndRetailerSeeder::class);

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->assignRole('SuperAdmin');
        $this->actingAs($this->adminUser);
    }

    public function test_trips_index_renders_successfully(): void
    {
        $response = $this->get(route('trips.index'));
        $response->assertStatus(200);
    }

    public function test_trip_can_be_created_and_decrements_central_warehouse_stock(): void
    {
        $road = Road::where('code', 'RD-A')->first();

        $product = Product::create([
            'name' => 'Special Toast Pack',
            'code' => 'TST-01',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 30.00,
            'wholesale_price' => 40.00,
            'warehouse_stock' => 150.00,
            'min_alert_quantity' => 10,
        ]);

        $response = $this->post(route('trips.store'), [
            'road_id' => $road->id,
            'trip_date' => now()->toDateString(),
            'vehicle_number' => 'DHK-METRO-11-2034',
            'driver_name' => 'Rafiqul Islam',
            'driver_phone' => '01711223344',
            'items' => [
                [
                    'product_id' => $product->id,
                    'loaded_quantity' => 50.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('trips.index'));
        $response->assertSessionHas('success');

        // Central warehouse stock deducted: 150 - 50 = 100
        $this->assertEquals(100.00, (float) $product->fresh()->warehouse_stock);

        // Trip record created
        $this->assertDatabaseHas('vehicle_trips', [
            'road_id' => $road->id,
            'vehicle_number' => 'DHK-METRO-11-2034',
            'status' => 'LOADED',
        ]);

        // Trip item created
        $this->assertDatabaseHas('vehicle_trip_items', [
            'product_id' => $product->id,
            'loaded_quantity' => 50.00,
        ]);

        // Stock movement recorded
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => StockMovementType::LoadToVehicle->value,
            'source_location' => 'CENTRAL_WAREHOUSE',
            'destination_location' => 'VEHICLE_TRANSIT',
            'quantity' => 50.00,
            'balance_before' => 150.00,
            'balance_after' => 100.00,
        ]);
    }

    public function test_trip_creation_prevents_loading_more_than_available_warehouse_stock(): void
    {
        $road = Road::where('code', 'RD-A')->first();

        $product = Product::create([
            'name' => 'Butter Biscuit Box',
            'code' => 'BSC-02',
            'packaging_type' => PackagingType::Cartoon->value,
            'cost_price' => 50.00,
            'wholesale_price' => 70.00,
            'warehouse_stock' => 10.00, // Only 10 available
            'min_alert_quantity' => 5,
        ]);

        // Attempt to load 25 (exceeds 10)
        $response = $this->post(route('trips.store'), [
            'road_id' => $road->id,
            'trip_date' => now()->toDateString(),
            'vehicle_number' => 'DHK-METRO-11-2034',
            'driver_name' => 'Rafiqul Islam',
            'items' => [
                [
                    'product_id' => $product->id,
                    'loaded_quantity' => 25.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('trips.index'));
        $response->assertSessionHas('error');

        // Warehouse stock must remain unchanged
        $this->assertEquals(10.00, (float) $product->fresh()->warehouse_stock);
    }

    public function test_cannot_load_product_with_zero_warehouse_stock_to_vehicle(): void
    {
        $road = Road::where('code', 'RD-A')->first();

        // Product created without prior supplier purchase (0 warehouse stock)
        $product = Product::create([
            'name' => 'Dry Cake Box',
            'code' => 'CAKE-01',
            'packaging_type' => PackagingType::Cartoon->value,
            'cost_price' => 50.00,
            'wholesale_price' => 70.00,
            'warehouse_stock' => 0.00, // 0 Stock
            'min_alert_quantity' => 5,
        ]);

        $response = $this->post(route('trips.store'), [
            'road_id' => $road->id,
            'trip_date' => now()->toDateString(),
            'vehicle_number' => 'DHK-METRO-11-2034',
            'driver_name' => 'Rafiqul Islam',
            'items' => [
                [
                    'product_id' => $product->id,
                    'loaded_quantity' => 10.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('trips.index'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Central Warehouse stock is 0', session('error'));
        $this->assertStringContainsString('purchase stock from a manufacturing supplier first', session('error'));

        // No trip items created
        $this->assertDatabaseMissing('vehicle_trip_items', [
            'product_id' => $product->id,
        ]);
    }

    public function test_trip_can_be_dispatched(): void
    {
        $road = Road::where('code', 'RD-B')->first();

        $trip = VehicleTrip::create([
            'trip_no' => 'TRIP-TEST-001',
            'road_id' => $road->id,
            'trip_date' => now()->toDateString(),
            'vehicle_number' => 'DHK-METRO-14-5512',
            'driver_name' => 'Anwar Hossain',
            'status' => TripStatus::Loaded,
        ]);

        $response = $this->patch(route('trips.dispatch', $trip));
        $response->assertSessionHas('success');

        $this->assertEquals('DISPATCHED', $trip->fresh()->status->value);
        $this->assertNotNull($trip->fresh()->dispatched_at);
    }

    public function test_trip_manifest_page_renders_successfully(): void
    {
        $road = Road::where('code', 'RD-C')->first();

        $trip = VehicleTrip::create([
            'trip_no' => 'TRIP-TEST-002',
            'road_id' => $road->id,
            'trip_date' => now()->toDateString(),
            'vehicle_number' => 'DHK-METRO-19-8801',
            'driver_name' => 'Kabir Ahmed',
            'status' => TripStatus::Loaded,
        ]);

        $response = $this->get(route('trips.show', $trip));
        $response->assertStatus(200);
    }
}
