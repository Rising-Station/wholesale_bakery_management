<?php

namespace Tests\Feature;

use App\Enums\PackagingType;
use App\Enums\PaymentMethod;
use App\Enums\ReturnType;
use App\Enums\StockMovementType;
use App\Enums\TripStatus;
use App\Models\Product;
use App\Models\RetailerShop;
use App\Models\Road;
use App\Models\SalesMemo;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VehicleTrip;
use App\Models\VehicleTripItem;
use Database\Seeders\RoadAndRetailerSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesMemoAndReturnsTest extends TestCase
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

    public function test_memos_index_renders_successfully(): void
    {
        $response = $this->get(route('memos.index'));
        $response->assertStatus(200);
    }

    public function test_sales_memo_can_be_created_with_subtotal_and_dual_ledger_due_update(): void
    {
        $road = Road::where('code', 'RD-A')->first();
        $retailer = RetailerShop::where('road_id', $road->id)->first();
        $retailer->update(['current_due' => 500.00]); // Previous Due = 500

        $product = Product::create([
            'name' => 'Chanachur 500g',
            'code' => 'CHN-01',
            'packaging_type' => PackagingType::Cartoon->value,
            'cost_price' => 60.00,
            'wholesale_price' => 80.00,
            'warehouse_stock' => 100.00,
            'min_alert_quantity' => 10,
        ]);

        // Sold 10 units at 80 BDT = 800 BDT.
        // Paid 300 BDT.
        // Total Payable = 500 (Prev) + 800 = 1300.
        // Due = 1300 - 300 = 1000.
        $response = $this->post(route('memos.store'), [
            'retailer_id' => $retailer->id,
            'road_id' => $road->id,
            'memo_date' => now()->toDateString(),
            'paid_amount' => 300.00,
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10.00,
                    'unit_price' => 80.00,
                ],
            ],
        ]);

        $memo = SalesMemo::latest('id')->first();
        $response->assertRedirect(route('memos.show', $memo));

        $this->assertDatabaseHas('sales_memos', [
            'id' => $memo->id,
            'retailer_id' => $retailer->id,
            'subtotal' => 800.00,
            'previous_due' => 500.00,
            'total_net_payable' => 1300.00,
            'paid_amount' => 300.00,
            'due_amount' => 1000.00,
            'memo_status' => 'PARTIAL',
        ]);

        // Retailer live current due updated to 1000
        $this->assertEquals(1000.00, (float) $retailer->fresh()->current_due);
    }

    public function test_sales_memo_creation_with_damage_return_deduction_routes_to_scrap_inventory(): void
    {
        $road = Road::where('code', 'RD-B')->first();
        $retailer = RetailerShop::where('road_id', $road->id)->first();
        $retailer->update(['current_due' => 0.00]);

        $soldProduct = Product::create([
            'name' => 'Butter Toast',
            'code' => 'TST-01',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 30.00,
            'wholesale_price' => 40.00,
            'warehouse_stock' => 50.00,
        ]);

        $damagedProduct = Product::create([
            'name' => 'Cake Pack',
            'code' => 'CK-01',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 15.00,
            'wholesale_price' => 20.00,
            'warehouse_stock' => 50.00,
        ]);

        // Sold 10 Toast @ 40 = 400 BDT
        // Returned 5 Damaged Cakes @ 20 = 100 BDT deduction (Scenario 1)
        // Net Bill = 400 - 100 = 300 BDT
        $response = $this->post(route('memos.store'), [
            'retailer_id' => $retailer->id,
            'road_id' => $road->id,
            'memo_date' => now()->toDateString(),
            'paid_amount' => 300.00,
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [
                [
                    'product_id' => $soldProduct->id,
                    'quantity' => 10.00,
                    'unit_price' => 40.00,
                ],
            ],
            'returns' => [
                [
                    'product_id' => $damagedProduct->id,
                    'return_type' => ReturnType::Damage->value,
                    'damage_type' => 'EXPIRED',
                    'quantity' => 5.00,
                    'unit_deduction_rate' => 20.00,
                ],
            ],
        ]);

        $memo = SalesMemo::latest('id')->first();
        $response->assertRedirect(route('memos.show', $memo));

        $this->assertEquals(100.00, (float) $memo->damage_deduction);
        $this->assertEquals(300.00, (float) $memo->grand_total);
        $this->assertEquals(0.00, (float) $memo->due_amount);
        $this->assertEquals('PAID', $memo->memo_status->value);

        // Damaged goods routed to damage_scrap_inventory (Quarantine)
        $this->assertDatabaseHas('damage_scrap_inventory', [
            'product_id' => $damagedProduct->id,
            'retailer_id' => $retailer->id,
            'quantity' => 5.00,
            'status' => 'QUARANTINED',
        ]);

        // Sellable warehouse stock of damaged product does NOT increase
        $this->assertEquals(50.00, (float) $damagedProduct->fresh()->warehouse_stock);
    }

    public function test_sales_memo_creation_with_product_exchange_deduction_increases_sellable_stock(): void
    {
        $road = Road::where('code', 'RD-C')->first();
        $retailer = RetailerShop::where('road_id', $road->id)->first();
        $retailer->update(['current_due' => 0.00]);

        $soldProduct = Product::create([
            'name' => 'Fruit Cake',
            'code' => 'FC-01',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 50.00,
            'wholesale_price' => 70.00,
            'warehouse_stock' => 20.00,
        ]);

        $exchangedProduct = Product::create([
            'name' => 'Dry Cake',
            'code' => 'DC-01',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 30.00,
            'wholesale_price' => 40.00,
            'warehouse_stock' => 10.00, // Starts with 10
        ]);

        // Sold 5 Fruit Cake @ 70 = 350 BDT
        // Exchanged 2 Dry Cake in good condition @ 40 = 80 BDT deduction (Scenario 2)
        // Net Bill = 350 - 80 = 270 BDT
        $response = $this->post(route('memos.store'), [
            'retailer_id' => $retailer->id,
            'road_id' => $road->id,
            'memo_date' => now()->toDateString(),
            'paid_amount' => 270.00,
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [
                [
                    'product_id' => $soldProduct->id,
                    'quantity' => 5.00,
                    'unit_price' => 70.00,
                ],
            ],
            'returns' => [
                [
                    'product_id' => $exchangedProduct->id,
                    'return_type' => ReturnType::Exchange->value,
                    'quantity' => 2.00,
                    'unit_deduction_rate' => 40.00,
                ],
            ],
        ]);

        $memo = SalesMemo::latest('id')->first();
        $response->assertRedirect(route('memos.show', $memo));

        $this->assertEquals(80.00, (float) $memo->exchange_deduction);
        $this->assertEquals(270.00, (float) $memo->grand_total);

        // Scenario 2: Sellable stock of exchanged product INCREASES (10 + 2 = 12)
        $this->assertEquals(12.00, (float) $exchangedProduct->fresh()->warehouse_stock);

        // Immutable stock movement logged with EXCHANGE_RETURN
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $exchangedProduct->id,
            'movement_type' => StockMovementType::ExchangeReturn->value,
            'quantity' => 2.00,
            'balance_before' => 10.00,
            'balance_after' => 12.00,
        ]);
    }

    public function test_vehicle_trip_stock_is_deducted_upon_sales_memo_generation(): void
    {
        $road = Road::where('code', 'RD-A')->first();
        $retailer = RetailerShop::where('road_id', $road->id)->first();

        $product = Product::create([
            'name' => 'Toast Pack',
            'code' => 'TST-09',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 30.00,
            'wholesale_price' => 40.00,
            'warehouse_stock' => 100.00,
        ]);

        $trip = VehicleTrip::create([
            'trip_no' => 'TRIP-2026-999',
            'road_id' => $road->id,
            'trip_date' => now()->toDateString(),
            'vehicle_number' => 'DHK-2233',
            'driver_name' => 'Faruk',
            'status' => TripStatus::Dispatched,
        ]);

        $tripItem = VehicleTripItem::create([
            'vehicle_trip_id' => $trip->id,
            'product_id' => $product->id,
            'loaded_quantity' => 50.00,
            'sold_quantity' => 0.00,
        ]);

        $response = $this->post(route('memos.store'), [
            'retailer_id' => $retailer->id,
            'road_id' => $road->id,
            'vehicle_trip_id' => $trip->id,
            'memo_date' => now()->toDateString(),
            'paid_amount' => 400.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10.00,
                    'unit_price' => 40.00,
                ],
            ],
        ]);

        $memo = SalesMemo::latest('id')->first();
        $response->assertRedirect(route('memos.show', $memo));

        // Vehicle trip item sold quantity updated from 0 to 10
        $this->assertEquals(10.00, (float) $tripItem->fresh()->sold_quantity);
    }

    public function test_direct_retailer_due_payment_collection_reduces_retailer_due(): void
    {
        $retailer = RetailerShop::first();
        $retailer->update(['current_due' => 2000.00]);

        $response = $this->post(route('memos.collect-due'), [
            'retailer_id' => $retailer->id,
            'road_id' => $retailer->road_id,
            'payment_method' => PaymentMethod::Bkash->value,
            'amount' => 1500.00,
            'payment_date' => now()->toDateString(),
            'transaction_ref' => 'BKASH-776655',
        ]);

        $response->assertSessionHas('success');

        // Due decreased from 2000 to 500
        $this->assertEquals(500.00, (float) $retailer->fresh()->current_due);

        $this->assertDatabaseHas('retailer_payments', [
            'retailer_id' => $retailer->id,
            'amount' => 1500.00,
            'payment_method' => 'BKASH',
        ]);
    }

    public function test_memo_show_page_renders_with_printable_thermal_and_a4_details(): void
    {
        $road = Road::first();
        $retailer = RetailerShop::first();

        $memo = SalesMemo::create([
            'memo_no' => 'MEMO-TEST-001',
            'retailer_id' => $retailer->id,
            'road_id' => $road->id,
            'memo_date' => now()->toDateString(),
            'subtotal' => 500,
            'grand_total' => 500,
            'total_net_payable' => 500,
            'paid_amount' => 500,
            'due_amount' => 0,
        ]);

        $response = $this->get(route('memos.show', $memo));
        $response->assertStatus(200);
    }

    public function test_damage_returns_audit_index_renders_successfully(): void
    {
        $response = $this->get(route('damage-returns.index'));
        $response->assertStatus(200);
    }

    public function test_supplier_return_claim_can_be_filed(): void
    {
        $supplier = Supplier::create([
            'name' => 'Pran Factory',
            'phone' => '01700000000',
        ]);

        $product = Product::create([
            'name' => 'Expired Toast',
            'code' => 'EXP-01',
            'cost_price' => 20.00,
            'wholesale_price' => 30.00,
        ]);

        $response = $this->post(route('damage-returns.claims.store'), [
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'quantity' => 50.00,
            'unit_cost' => 20.00,
            'reason' => 'Factory mold defect found in packaging',
            'notes' => 'Batch #9923',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('supplier_return_claims', [
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'quantity' => 50.00,
            'claim_amount' => 1000.00,
            'status' => 'PENDING',
        ]);
    }

    public function test_cannot_generate_memo_for_product_with_zero_stock_on_vehicle_trip(): void
    {
        $road = Road::where('code', 'RD-A')->first();
        $retailer = RetailerShop::where('road_id', $road->id)->first();

        // Product with 0 loaded stock on trip
        $product = Product::create([
            'name' => 'Zero Stock Bread',
            'code' => 'BRD-00',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 20.00,
            'wholesale_price' => 30.00,
            'warehouse_stock' => 50.00,
        ]);

        $trip = VehicleTrip::create([
            'trip_no' => 'TRIP-STOCK-TEST-01',
            'road_id' => $road->id,
            'trip_date' => now()->toDateString(),
            'vehicle_number' => 'DHK-METRO-11-2034',
            'driver_name' => 'Rafiqul Islam',
            'status' => TripStatus::Loaded,
        ]);

        // Product not loaded on this vehicle trip at all
        $response = $this->post(route('memos.store'), [
            'retailer_id' => $retailer->id,
            'road_id' => $road->id,
            'vehicle_trip_id' => $trip->id,
            'memo_date' => now()->toDateString(),
            'paid_amount' => 0.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5.00,
                    'unit_price' => 30.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('memos.index'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('was not loaded on this vehicle trip', session('error'));
        $this->assertStringContainsString('0 stock cannot be sold', session('error'));

        // Assert memo was not created
        $this->assertDatabaseMissing('sales_memos', [
            'vehicle_trip_id' => $trip->id,
        ]);
    }

    public function test_cannot_generate_memo_for_product_with_zero_warehouse_stock_in_direct_sale(): void
    {
        $road = Road::where('code', 'RD-A')->first();
        $retailer = RetailerShop::where('road_id', $road->id)->first();

        // Product with 0 warehouse stock
        $product = Product::create([
            'name' => 'Unpurchased Snack',
            'code' => 'SNK-00',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 15.00,
            'wholesale_price' => 25.00,
            'warehouse_stock' => 0.00, // 0 warehouse stock
        ]);

        $response = $this->post(route('memos.store'), [
            'retailer_id' => $retailer->id,
            'road_id' => $road->id,
            'vehicle_trip_id' => null, // Direct central warehouse sale
            'memo_date' => now()->toDateString(),
            'paid_amount' => 0.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10.00,
                    'unit_price' => 25.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('memos.index'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Stock is 0', session('error'));
        $this->assertStringContainsString('purchase stock from a manufacturing supplier first', session('error'));

        // Assert memo was not created
        $this->assertDatabaseMissing('sales_memo_items', [
            'product_id' => $product->id,
        ]);
    }
}
