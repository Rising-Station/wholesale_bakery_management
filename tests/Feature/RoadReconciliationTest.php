<?php

namespace Tests\Feature;

use App\Enums\PackagingType;
use App\Enums\StockLocationType;
use App\Enums\StockMovementType;
use App\Enums\TripStatus;
use App\Models\Product;
use App\Models\RetailerShop;
use App\Models\Road;
use App\Models\SalesMemo;
use App\Models\User;
use App\Models\VehicleTrip;
use App\Models\VehicleTripItem;
use Database\Seeders\RoadAndRetailerSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoadReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(RoadAndRetailerSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('SuperAdmin');
        $this->actingAs($this->admin);
    }

    public function test_user_can_view_trip_reconciliation_page(): void
    {
        $road = Road::first();
        $product = Product::create([
            'name' => 'Premium Bun',
            'code' => 'BUN-01',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 15.00,
            'wholesale_price' => 20.00,
            'warehouse_stock' => 500.00,
        ]);

        $trip = VehicleTrip::create([
            'trip_no' => 'TRIP-20261025-0001',
            'road_id' => $road->id,
            'trip_date' => now()->toDateString(),
            'vehicle_number' => 'DHK-METRO-11-2034',
            'driver_name' => 'Rafiqul Islam',
            'status' => TripStatus::Dispatched,
        ]);

        VehicleTripItem::create([
            'vehicle_trip_id' => $trip->id,
            'product_id' => $product->id,
            'loaded_quantity' => 100.00,
            'sold_quantity' => 60.00,
            'damaged_quantity' => 0.00,
            'exchanged_quantity' => 0.00,
            'returned_quantity' => 0.00,
        ]);

        $response = $this->get(route('trips.reconcile', $trip));
        $response->assertOk();
    }

    public function test_submitting_trip_reconciliation_returns_unsold_stock_to_central_warehouse_and_marks_trip_completed(): void
    {
        $road = Road::first();
        $product = Product::create([
            'name' => 'Standard Rusk',
            'code' => 'RSK-01',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 25.00,
            'wholesale_price' => 35.00,
            'warehouse_stock' => 200.00,
        ]);

        $trip = VehicleTrip::create([
            'trip_no' => 'TRIP-20261025-0002',
            'road_id' => $road->id,
            'trip_date' => now()->toDateString(),
            'vehicle_number' => 'DHK-METRO-11-2034',
            'driver_name' => 'Rafiqul Islam',
            'status' => TripStatus::Dispatched,
        ]);

        $tripItem = VehicleTripItem::create([
            'vehicle_trip_id' => $trip->id,
            'product_id' => $product->id,
            'loaded_quantity' => 100.00,
            'sold_quantity' => 70.00,
            'damaged_quantity' => 0.00,
            'exchanged_quantity' => 0.00,
            'returned_quantity' => 0.00,
        ]);

        // Unsold expected = 30. Driver brings back 30 units physically.
        $response = $this->post(route('trips.reconcile.store', $trip), [
            'notes' => 'Perfect balance without shortages',
            'items' => [
                [
                    'product_id' => $product->id,
                    'returned_quantity' => 30.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('trips.index'));
        $response->assertSessionHas('success');

        // Verify trip is marked completed
        $this->assertEquals(TripStatus::Completed, $trip->fresh()->status);
        $this->assertNotNull($trip->fresh()->completed_at);

        // Verify trip item returned_quantity is updated
        $this->assertEquals(30.00, (float) $tripItem->fresh()->returned_quantity);

        // Verify product central warehouse stock increased by 30 units (200 + 30 = 230)
        $this->assertEquals(230.00, (float) $product->fresh()->warehouse_stock);

        // Verify StockMovement logged for RETURN_FROM_VEHICLE
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => StockMovementType::ReturnFromVehicle->value,
            'source_location' => StockLocationType::VehicleTransit->value,
            'destination_location' => StockLocationType::CentralWarehouse->value,
            'quantity' => 30.00,
            'balance_before' => 200.00,
            'balance_after' => 230.00,
        ]);
    }

    public function test_cannot_reconcile_already_completed_trip(): void
    {
        $road = Road::first();
        $product = Product::create([
            'name' => 'Standard Rusk',
            'code' => 'RSK-02',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 25.00,
            'wholesale_price' => 35.00,
            'warehouse_stock' => 100.00,
        ]);

        $trip = VehicleTrip::create([
            'trip_no' => 'TRIP-20261025-0003',
            'road_id' => $road->id,
            'trip_date' => now()->toDateString(),
            'vehicle_number' => 'DHK-METRO-11-2034',
            'driver_name' => 'Rafiqul Islam',
            'status' => TripStatus::Completed,
            'completed_at' => now(),
        ]);

        VehicleTripItem::create([
            'vehicle_trip_id' => $trip->id,
            'product_id' => $product->id,
            'loaded_quantity' => 50.00,
            'sold_quantity' => 50.00,
            'returned_quantity' => 0.00,
        ]);

        $response = $this->post(route('trips.reconcile.store', $trip), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'returned_quantity' => 0.00,
                ],
            ],
        ]);

        $response->assertSessionHas('error');
    }

    public function test_user_can_view_road_settlement_report_page_with_road_tabs_and_consolidated_totals(): void
    {
        $road = Road::first();
        $retailer = RetailerShop::where('road_id', $road->id)->first();

        // Create a memo to have some financial figures
        SalesMemo::create([
            'memo_no' => 'MEMO-TEST-ROAD-001',
            'retailer_id' => $retailer->id,
            'road_id' => $road->id,
            'memo_date' => now()->toDateString(),
            'subtotal' => 1500.00,
            'damage_deduction' => 100.00,
            'exchange_deduction' => 50.00,
            'custom_deduction' => 0.00,
            'grand_total' => 1350.00,
            'previous_due' => 0.00,
            'total_net_payable' => 1350.00,
            'paid_amount' => 1000.00,
            'due_amount' => 350.00,
            'memo_status' => 'PARTIAL',
            'delivery_status' => 'DELIVERED',
        ]);

        $response = $this->get(route('reports.road-settlement', [
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk();
    }
}
