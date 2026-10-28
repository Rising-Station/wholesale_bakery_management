<?php

namespace Tests\Feature;

use App\Enums\DamageScrapCategory;
use App\Enums\DamageType;
use App\Enums\PackagingType;
use App\Enums\PaymentMethod;
use App\Enums\ReturnConditionStatus;
use App\Enums\ReturnDestinationLocation;
use App\Enums\ReturnType;
use App\Enums\ReturnUnit;
use App\Enums\TripStatus;
use App\Models\DamageReturn;
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

class PartialExchangeTest extends TestCase
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

    /**
     * Test the exact scenario:
     * Retailer returns 2 kg of Chanachur from a 5kg packet (৳850/5kg = ৳170/kg => ৳340 credit)
     * and exchanges it for 1 unit of Cake (৳340).
     */
    public function test_partial_sub_unit_exchange_calculates_correct_credit_and_routes_to_repack_holding(): void
    {
        $road = Road::where('code', 'RD-A')->first();
        $retailer = RetailerShop::where('road_id', $road->id)->first();
        $retailer->update(['current_due' => 1500.00]);

        $chanachur = Product::create([
            'name' => 'Spicy Bombay Chanachur (5kg Packet)',
            'code' => 'CHN-5KG-01',
            'packaging_type' => PackagingType::Cartoon->value,
            'cost_price' => 700.00,
            'wholesale_price' => 850.00,
            'weight_per_unit_kg' => 5.000,
            'warehouse_stock' => 20.00,
            'min_alert_quantity' => 5,
        ]);

        $cake = Product::create([
            'name' => 'Special Pound Cake',
            'code' => 'CK-01',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 250.00,
            'wholesale_price' => 340.00,
            'weight_per_unit_kg' => 0.800,
            'warehouse_stock' => 50.00,
            'min_alert_quantity' => 10,
        ]);

        // Retailer takes 1 Cake (৳340.00) and returns 2 kg of Chanachur (৳170/kg * 2 = ৳340.00 credit)
        $response = $this->post(route('memos.store'), [
            'retailer_id' => $retailer->id,
            'road_id' => $road->id,
            'memo_date' => now()->toDateString(),
            'paid_amount' => 0.00,
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [
                [
                    'product_id' => $cake->id,
                    'quantity' => 1.00,
                    'unit_price' => 340.00,
                ],
            ],
            'returns' => [
                [
                    'product_id' => $chanachur->id,
                    'return_type' => ReturnType::Exchange->value,
                    'return_unit' => ReturnUnit::Kg->value,
                    'sub_unit_quantity' => 2.00,
                    'quantity' => 0.40,
                    'unit_deduction_rate' => 170.00,
                    'condition_status' => ReturnConditionStatus::BrokenPacket->value,
                    'damage_type' => DamageType::BrokenPacket->value,
                ],
            ],
        ]);

        $memo = SalesMemo::latest('id')->first();
        $this->assertNotNull($memo);
        $response->assertRedirect(route('memos.show', $memo));

        // 1. Verify Financial Ledger Calculations
        $this->assertEquals(340.00, (float) $memo->subtotal);
        $this->assertEquals(340.00, (float) $memo->exchange_deduction);
        $this->assertEquals(0.00, (float) $memo->damage_deduction);
        $this->assertEquals(0.00, (float) $memo->grand_total); // 340 - 340 = 0
        $this->assertEquals(1500.00, (float) $memo->previous_due);
        $this->assertEquals(1500.00, (float) $memo->total_net_payable);
        $this->assertEquals(1500.00, (float) $memo->due_amount);
        $this->assertEquals(1500.00, (float) $retailer->fresh()->current_due); // Due unchanged

        // 2. Verify Cake Stock decreased by 1
        $this->assertEquals(49.00, (float) $cake->fresh()->warehouse_stock);

        // 3. Verify Chanachur SEALED stock was NOT incremented (remains 20.00)
        $this->assertEquals(20.00, (float) $chanachur->fresh()->warehouse_stock);

        // 4. Verify Repackaging Scrap Inventory Quarantine entry created
        $this->assertDatabaseHas('damage_scrap_inventory', [
            'product_id' => $chanachur->id,
            'retailer_id' => $retailer->id,
            'category' => DamageScrapCategory::RepackHolding->value,
            'damage_type' => DamageType::BrokenPacket->value,
            'quantity' => 0.40,
            'weight_kg' => 2.00,
            'return_unit' => ReturnUnit::Kg->value,
            'status' => 'REPACK_HOLDING',
        ]);

        // 5. Verify DamageReturn audit trail record
        $this->assertDatabaseHas('damage_returns', [
            'sales_memo_id' => $memo->id,
            'product_id' => $chanachur->id,
            'return_type' => ReturnType::Exchange->value,
            'return_unit' => ReturnUnit::Kg->value,
            'sub_unit_quantity' => 2.00,
            'conversion_factor' => 0.40,
            'condition_status' => ReturnConditionStatus::BrokenPacket->value,
            'destination_location' => ReturnDestinationLocation::RepackHolding->value,
            'affects_sellable_stock' => false,
            'total_deduction_amount' => 340.00,
        ]);
    }

    /**
     * Test full sealed package exchange correctly restores sellable stock
     * while partial broken package does not.
     */
    public function test_full_sealed_exchange_restores_sellable_stock_cleanly(): void
    {
        $road = Road::where('code', 'RD-B')->first();
        $retailer = RetailerShop::where('road_id', $road->id)->first();

        $chanachur = Product::create([
            'name' => 'Spicy Bombay Chanachur (5kg Packet)',
            'code' => 'CHN-5KG-02',
            'packaging_type' => PackagingType::Cartoon->value,
            'cost_price' => 700.00,
            'wholesale_price' => 850.00,
            'weight_per_unit_kg' => 5.000,
            'warehouse_stock' => 10.00,
            'min_alert_quantity' => 5,
        ]);

        $cake = Product::create([
            'name' => 'Special Pound Cake Box',
            'code' => 'CK-02',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 750.00,
            'wholesale_price' => 900.00,
            'warehouse_stock' => 30.00,
        ]);

        // Retailer exchanges 1 full sealed 5kg packet of Chanachur for 1 Cake Box
        $this->post(route('memos.store'), [
            'retailer_id' => $retailer->id,
            'road_id' => $road->id,
            'memo_date' => now()->toDateString(),
            'paid_amount' => 50.00, // 900 - 850 = 50 net bill, paid in full
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [
                [
                    'product_id' => $cake->id,
                    'quantity' => 1.00,
                    'unit_price' => 900.00,
                ],
            ],
            'returns' => [
                [
                    'product_id' => $chanachur->id,
                    'return_type' => ReturnType::Exchange->value,
                    'return_unit' => 'PACKAGE',
                    'quantity' => 1.00,
                    'unit_deduction_rate' => 850.00,
                    'condition_status' => 'GOOD_SEALED',
                ],
            ],
        ]);

        // Central warehouse stock of Chanachur increased by 1 full sealed package (from 10 to 11)
        $this->assertEquals(11.00, (float) $chanachur->fresh()->warehouse_stock);

        // No repack holding created for full sealed pack
        $this->assertDatabaseMissing('damage_scrap_inventory', [
            'product_id' => $chanachur->id,
            'category' => 'REPACK_HOLDING',
        ]);
    }

    /**
     * Test Vehicle Trip context: partial broken package return does not inflate
     * vehicle van sellable stock.
     */
    public function test_partial_exchange_on_vehicle_trip_does_not_inflate_van_sellable_stock(): void
    {
        $road = Road::where('code', 'RD-A')->first();
        $retailer = RetailerShop::where('road_id', $road->id)->first();

        $trip = VehicleTrip::create([
            'trip_no' => 'TRIP-PARTIAL-01',
            'road_id' => $road->id,
            'vehicle_number' => 'DHK-METRO-1122',
            'driver_name' => 'Rafiqul Islam',
            'driver_phone' => '01711223344',
            'status' => TripStatus::Dispatched->value,
            'trip_date' => now()->toDateString(),
        ]);

        $chanachur = Product::create([
            'name' => 'Spicy Bombay Chanachur (5kg Packet)',
            'code' => 'CHN-5KG-03',
            'packaging_type' => PackagingType::Cartoon->value,
            'cost_price' => 700.00,
            'wholesale_price' => 850.00,
            'weight_per_unit_kg' => 5.000,
            'warehouse_stock' => 20.00,
        ]);

        $cake = Product::create([
            'name' => 'Mini Cake',
            'code' => 'CK-03',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 100.00,
            'wholesale_price' => 170.00,
            'warehouse_stock' => 50.00,
        ]);

        $cakeTripItem = VehicleTripItem::create([
            'vehicle_trip_id' => $trip->id,
            'product_id' => $cake->id,
            'loaded_quantity' => 10.00,
            'sold_quantity' => 0.00,
            'damaged_quantity' => 0.00,
            'exchanged_quantity' => 0.00,
            'unit_cost' => 100.00,
        ]);

        // Van sells 2 Mini Cakes (৳340) and accepts 2 kg Chanachur exchange (৳340)
        $this->post(route('memos.store'), [
            'vehicle_trip_id' => $trip->id,
            'retailer_id' => $retailer->id,
            'road_id' => $road->id,
            'memo_date' => now()->toDateString(),
            'paid_amount' => 0.00,
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [
                [
                    'product_id' => $cake->id,
                    'quantity' => 2.00,
                    'unit_price' => 170.00,
                ],
            ],
            'returns' => [
                [
                    'product_id' => $chanachur->id,
                    'return_type' => ReturnType::Exchange->value,
                    'return_unit' => 'KG',
                    'sub_unit_quantity' => 2.00,
                    'quantity' => 0.40,
                    'unit_deduction_rate' => 170.00,
                    'condition_status' => 'BROKEN_PACKET',
                ],
            ],
        ]);

        // Cake sold quantity incremented to 2
        $this->assertEquals(2.00, (float) $cakeTripItem->fresh()->sold_quantity);

        // Repack holding record created
        $this->assertDatabaseHas('damage_scrap_inventory', [
            'product_id' => $chanachur->id,
            'category' => 'REPACK_HOLDING',
            'damage_type' => DamageType::BrokenPacket->value,
            'weight_kg' => 2.00,
        ]);
    }

    /**
     * Test Cartoon product (e.g. Cake 24 pcs = 1 Cartoon):
     * Returning 5 pcs as DAMAGED calculates 5 * (480/24 = 20) = ৳100 credit
     * and routes to quarantine scrap with pieces_count = 5.
     */
    public function test_cartoon_cake_pieces_damage_return_routes_to_scrap_quarantine(): void
    {
        $road = Road::where('code', 'RD-A')->first();
        $retailer = RetailerShop::where('road_id', $road->id)->first();

        $cakeCartoon = Product::create([
            'name' => 'Vanilla Plain Cake (Cartoon)',
            'code' => 'CAKE-CTN-TEST',
            'packaging_type' => PackagingType::Cartoon->value,
            'units_per_cartoon' => 24,
            'cost_price' => 360.00,
            'wholesale_price' => 480.00, // ৳480 / 24 = ৳20/pc
            'warehouse_stock' => 50.00,
        ]);

        $bread = Product::create([
            'name' => 'Family Bread',
            'code' => 'BRD-01',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 40.00,
            'wholesale_price' => 60.00,
            'warehouse_stock' => 100.00,
        ]);

        // Retailer buys 5 Breads (৳300) and returns 5 damaged pcs of Cake from a cartoon (5 * ৳20 = ৳100 deduction)
        $response = $this->post(route('memos.store'), [
            'retailer_id' => $retailer->id,
            'road_id' => $road->id,
            'memo_date' => now()->toDateString(),
            'paid_amount' => 200.00, // 300 - 100 = 200 net bill, paid in full
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [
                [
                    'product_id' => $bread->id,
                    'quantity' => 5.00,
                    'unit_price' => 60.00,
                ],
            ],
            'returns' => [
                [
                    'product_id' => $cakeCartoon->id,
                    'return_type' => ReturnType::Damage->value,
                    'return_unit' => ReturnUnit::Pcs->value,
                    'sub_unit_quantity' => 5,
                    'quantity' => round(5 / 24, 3), // 0.208
                    'unit_deduction_rate' => 20.00,
                    'damage_type' => 'DAMAGED',
                ],
            ],
        ]);

        $memo = SalesMemo::latest('id')->first();
        $this->assertNotNull($memo);
        $response->assertRedirect(route('memos.show', $memo));

        // Memo financial check
        $this->assertEquals(300.00, (float) $memo->subtotal);
        $this->assertEquals(100.00, (float) $memo->damage_deduction);
        $this->assertEquals(200.00, (float) $memo->grand_total);
        $this->assertEquals(0.00, (float) $memo->due_amount);

        // Cake warehouse stock is unaffected (does NOT increase)
        $this->assertEquals(50.00, (float) $cakeCartoon->fresh()->warehouse_stock);

        // Scrap quarantine record created with pieces_count = 5
        $this->assertDatabaseHas('damage_scrap_inventory', [
            'product_id' => $cakeCartoon->id,
            'retailer_id' => $retailer->id,
            'damage_type' => 'DAMAGED',
            'category' => DamageScrapCategory::ScrapDisposal->value,
            'return_unit' => ReturnUnit::Pcs->value,
            'pieces_count' => 5,
            'status' => 'QUARANTINED',
        ]);
    }

    /**
     * Test Cartoon product (e.g. Cake 24 pcs = 1 Cartoon):
     * Returning 5 pcs as PRODUCT EXCHANGE calculates ৳100 credit,
     * routes to Repack Bay Holding without inflating sealed cartoon stock.
     */
    public function test_cartoon_cake_pieces_exchange_return_routes_to_repack_holding(): void
    {
        $road = Road::where('code', 'RD-B')->first();
        $retailer = RetailerShop::where('road_id', $road->id)->first();

        $cakeCartoon = Product::create([
            'name' => 'Chocolate Cake (Cartoon)',
            'code' => 'CAKE-CTN-CHOC',
            'packaging_type' => PackagingType::Cartoon->value,
            'units_per_cartoon' => 24,
            'cost_price' => 380.00,
            'wholesale_price' => 480.00, // ৳480 / 24 = ৳20/pc
            'warehouse_stock' => 10.00,
        ]);

        $toast = Product::create([
            'name' => 'Toast Biscuit Pack',
            'code' => 'TST-02',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 20.00,
            'wholesale_price' => 30.00,
            'warehouse_stock' => 50.00,
        ]);

        // Retailer takes 4 Toast packs (4 * 30 = ৳120) and exchanges 5 pcs of Cake (5 * 20 = ৳100 credit)
        // Net payable = 120 - 100 = ৳20
        $this->post(route('memos.store'), [
            'retailer_id' => $retailer->id,
            'road_id' => $road->id,
            'memo_date' => now()->toDateString(),
            'paid_amount' => 20.00,
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [
                [
                    'product_id' => $toast->id,
                    'quantity' => 4.00,
                    'unit_price' => 30.00,
                ],
            ],
            'returns' => [
                [
                    'product_id' => $cakeCartoon->id,
                    'return_type' => ReturnType::Exchange->value,
                    'return_unit' => ReturnUnit::Pcs->value,
                    'sub_unit_quantity' => 5,
                    'quantity' => round(5 / 24, 3), // 0.208
                    'unit_deduction_rate' => 20.00,
                    'condition_status' => ReturnConditionStatus::BrokenPacket->value,
                ],
            ],
        ]);

        $memo = SalesMemo::latest('id')->first();
        $this->assertEquals(120.00, (float) $memo->subtotal);
        $this->assertEquals(100.00, (float) $memo->exchange_deduction);
        $this->assertEquals(20.00, (float) $memo->grand_total);
        $this->assertEquals(0.00, (float) $memo->due_amount);

        // Wholesale sealed cartoon stock does NOT increase (remains 10.00 cartoons)
        $this->assertEquals(10.00, (float) $cakeCartoon->fresh()->warehouse_stock);

        // Repack holding record created with pieces_count = 5 and status = REPACK_HOLDING
        $this->assertDatabaseHas('damage_scrap_inventory', [
            'product_id' => $cakeCartoon->id,
            'retailer_id' => $retailer->id,
            'damage_type' => DamageType::BrokenPacket->value,
            'category' => DamageScrapCategory::RepackHolding->value,
            'return_unit' => ReturnUnit::Pcs->value,
            'pieces_count' => 5,
            'status' => 'REPACK_HOLDING',
        ]);

        // Damage return audit record has PCS unit
        $this->assertDatabaseHas('damage_returns', [
            'sales_memo_id' => $memo->id,
            'product_id' => $cakeCartoon->id,
            'return_unit' => ReturnUnit::Pcs->value,
            'sub_unit_quantity' => 5,
            'destination_location' => ReturnDestinationLocation::RepackHolding->value,
            'affects_sellable_stock' => false,
            'total_deduction_amount' => 100.00,
        ]);
    }
}
