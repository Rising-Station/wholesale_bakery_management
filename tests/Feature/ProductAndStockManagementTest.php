<?php

namespace Tests\Feature;

use App\Enums\PackagingType;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAndStockManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->adminUser = User::factory()->create([
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('SuperAdmin');
        $this->actingAs($this->adminUser);
    }

    public function test_products_index_renders_successfully(): void
    {
        $response = $this->get(route('products.index'));
        $response->assertStatus(200);
    }

    public function test_product_category_can_be_created(): void
    {
        $response = $this->post(route('products.categories.store'), [
            'name' => 'Premium Toast',
            'description' => 'Crispy oven baked toasts',
            'is_active' => true,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('product_categories', [
            'name' => 'Premium Toast',
            'slug' => 'premium-toast',
        ]);
    }

    public function test_product_with_cartoon_packaging_can_be_created_with_initial_stock_movement(): void
    {
        $category = ProductCategory::create([
            'name' => 'Chanachur',
            'slug' => 'chanachur',
        ]);

        $response = $this->post(route('products.store'), [
            'category_id' => $category->id,
            'name' => 'Mominul Special Chanachur 500g',
            'code' => 'CHN-500',
            'packaging_type' => PackagingType::Cartoon->value,
            'units_per_cartoon' => 24,
            'weight_per_unit_kg' => 0.500,
            'cost_price' => 75.00,
            'wholesale_price' => 90.00,
            'warehouse_stock' => 100,
            'min_alert_quantity' => 15,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'code' => 'CHN-500',
            'packaging_type' => 'CARTOON',
            'units_per_cartoon' => 24,
            'warehouse_stock' => 100.00,
        ]);

        // Verify immutable opening stock audit movement
        $product = Product::where('code', 'CHN-500')->first();
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => StockMovementType::AuditAdjustment->value,
            'quantity' => 100.00,
            'balance_after' => 100.00,
        ]);
    }

    public function test_product_with_packet_5kg_packaging_can_be_created(): void
    {
        $response = $this->post(route('products.store'), [
            'name' => 'Bulk Crispy Toast 5kg',
            'code' => 'TST-5KG',
            'packaging_type' => PackagingType::Packet5kg->value,
            'weight_per_unit_kg' => 5.000,
            'cost_price' => 450.00,
            'wholesale_price' => 520.00,
            'warehouse_stock' => 30,
            'min_alert_quantity' => 5,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'code' => 'TST-5KG',
            'packaging_type' => 'PACKET_5KG',
            'weight_per_unit_kg' => 5.000,
        ]);
    }

    public function test_product_with_packet_12pcs_packaging_can_be_created(): void
    {
        $response = $this->post(route('products.store'), [
            'name' => 'Cup Cake Box (12 Pcs)',
            'code' => 'CAKE-12PC',
            'packaging_type' => PackagingType::Packet12pcs->value,
            'pieces_per_packet' => 12,
            'cost_price' => 110.00,
            'wholesale_price' => 135.00,
            'warehouse_stock' => 50,
            'min_alert_quantity' => 10,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'code' => 'CAKE-12PC',
            'packaging_type' => 'PACKET_12PCS',
            'pieces_per_packet' => 12,
        ]);
    }

    public function test_product_can_be_updated(): void
    {
        $product = Product::create([
            'name' => 'Original Butter Toast',
            'code' => 'TST-ORIG',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 40.00,
            'wholesale_price' => 50.00,
            'warehouse_stock' => 20,
            'min_alert_quantity' => 5,
            'is_active' => true,
        ]);

        $response = $this->put(route('products.update', $product), [
            'name' => 'Original Butter Toast (Updated Recipe)',
            'code' => 'TST-ORIG',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 42.00,
            'wholesale_price' => 55.00,
            'min_alert_quantity' => 8,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Original Butter Toast (Updated Recipe)',
            'cost_price' => 42.00,
            'wholesale_price' => 55.00,
        ]);
    }

    public function test_product_with_stock_movements_cannot_be_deleted(): void
    {
        $product = Product::create([
            'name' => 'Milk Bread 400g',
            'code' => 'BRD-400',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 30.00,
            'wholesale_price' => 38.00,
            'warehouse_stock' => 10,
            'min_alert_quantity' => 5,
        ]);

        // Log a stock movement
        StockMovement::create([
            'product_id' => $product->id,
            'movement_type' => StockMovementType::PurchaseIn->value,
            'source_location' => 'CENTRAL_WAREHOUSE',
            'destination_location' => 'CENTRAL_WAREHOUSE',
            'quantity' => 10,
            'balance_before' => 0,
            'balance_after' => 10,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->delete(route('products.destroy', $product));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_stock_overview_renders_and_filters_low_stock(): void
    {
        Product::create([
            'name' => 'Normal Stock Biscuit',
            'code' => 'BSC-01',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 10.00,
            'wholesale_price' => 15.00,
            'warehouse_stock' => 100,
            'min_alert_quantity' => 10,
        ]);

        Product::create([
            'name' => 'Low Stock Cake',
            'code' => 'CK-01',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 20.00,
            'wholesale_price' => 25.00,
            'warehouse_stock' => 3, // <= 10 (Low Stock)
            'min_alert_quantity' => 10,
        ]);

        $response = $this->get(route('stock.index'));
        $response->assertStatus(200);

        $lowStockResponse = $this->get(route('stock.index', ['low_stock' => 1]));
        $lowStockResponse->assertStatus(200);
    }

    public function test_stock_movements_ledger_renders(): void
    {
        $response = $this->get(route('stock.movements'));
        $response->assertStatus(200);
    }

    public function test_physical_stock_count_audit_adjustment_updates_stock_and_records_movements(): void
    {
        $product = Product::create([
            'name' => 'Dry Cake Box',
            'code' => 'DC-01',
            'packaging_type' => PackagingType::Cartoon->value,
            'cost_price' => 60.00,
            'wholesale_price' => 80.00,
            'warehouse_stock' => 50,
            'min_alert_quantity' => 10,
        ]);

        // Physical count reveals 45 items (shrinkage / 5 missing)
        $response = $this->post(route('stock-adjustments.store'), [
            'adjustment_date' => now()->toDateString(),
            'reason' => 'Monthly Physical Stock Audit',
            'notes' => 'Found 5 broken boxes during audit',
            'items' => [
                [
                    'product_id' => $product->id,
                    'physical_stock' => 45,
                ],
            ],
        ]);

        $response->assertRedirect(route('stock-adjustments.index'));
        $response->assertSessionHas('success');

        // Product stock updated from 50 to 45
        $this->assertEquals(45, $product->fresh()->warehouse_stock);

        // Stock adjustment record created
        $this->assertDatabaseHas('stock_adjustments', [
            'reason' => 'Monthly Physical Stock Audit',
            'status' => 'APPROVED',
        ]);

        // Immutable stock movement logged with REDUCE
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => StockMovementType::AuditAdjustment->value,
            'quantity' => 5.00,
            'balance_before' => 50.00,
            'balance_after' => 45.00,
        ]);
    }

    public function test_product_validation_rules_enforce_required_fields(): void
    {
        $response = $this->post(route('products.store'), []);
        $response->assertSessionHasErrors(['name', 'code', 'packaging_type', 'cost_price', 'wholesale_price']);
    }

    public function test_product_category_can_be_updated(): void
    {
        $category = ProductCategory::create([
            'name' => 'Old Category Name',
            'slug' => 'old-category-name',
            'is_active' => true,
        ]);

        $response = $this->put(route('products.categories.update', $category->id), [
            'name' => 'Updated Category Name',
            'description' => 'Updated description text',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'name' => 'Updated Category Name',
        ]);
    }

    public function test_product_category_can_be_deleted_when_no_products_exist(): void
    {
        $category = ProductCategory::create([
            'name' => 'Temporary Category',
            'slug' => 'temporary-category',
            'is_active' => true,
        ]);

        $response = $this->delete(route('products.categories.destroy', $category->id));

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseMissing('product_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_product_category_cannot_be_deleted_when_products_are_assigned(): void
    {
        $category = ProductCategory::create([
            'name' => 'Protected Category',
            'slug' => 'protected-category',
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Associated Bun',
            'code' => 'BUN-999',
            'packaging_type' => PackagingType::Pieces->value,
            'cost_price' => 10,
            'wholesale_price' => 15,
            'warehouse_stock' => 5,
        ]);

        $response = $this->delete(route('products.categories.destroy', $category->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_unit_crud_operations(): void
    {
        // Store
        $response = $this->post(route('products.units.store'), [
            'name' => 'Gram',
            'short_code' => 'gm',
            'description' => 'Weight unit',
            'is_active' => true,
        ]);
        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('units', ['short_code' => 'gm']);

        $unit = Unit::where('short_code', 'gm')->first();

        // Update
        $updateResponse = $this->put(route('products.units.update', $unit->id), [
            'name' => 'Gram (G)',
            'short_code' => 'g',
            'is_active' => true,
        ]);
        $updateResponse->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('units', ['id' => $unit->id, 'short_code' => 'g']);

        // Destroy
        $deleteResponse = $this->delete(route('products.units.destroy', $unit->id));
        $deleteResponse->assertRedirect(route('products.index'));
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    public function test_packaging_type_crud_operations(): void
    {
        // Store
        $response = $this->post(route('products.packaging-types.store'), [
            'name' => 'Big Master Crate',
            'code' => 'MASTER_CRATE',
            'short_label' => 'CRATE',
            'description' => 'Heavy plastic crate',
            'is_active' => true,
        ]);
        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('packaging_types', ['code' => 'MASTER_CRATE']);

        $pkg = \App\Models\PackagingType::where('code', 'MASTER_CRATE')->first();

        // Update
        $updateResponse = $this->put(route('products.packaging-types.update', $pkg->id), [
            'name' => 'Heavy Duty Plastic Crate',
            'code' => 'HEAVY_CRATE',
            'short_label' => 'HCRT',
            'is_active' => true,
        ]);
        $updateResponse->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('packaging_types', ['id' => $pkg->id, 'code' => 'HEAVY_CRATE']);

        // Destroy
        $deleteResponse = $this->delete(route('products.packaging-types.destroy', $pkg->id));
        $deleteResponse->assertRedirect(route('products.index'));
        $this->assertDatabaseMissing('packaging_types', ['id' => $pkg->id]);
    }
}
