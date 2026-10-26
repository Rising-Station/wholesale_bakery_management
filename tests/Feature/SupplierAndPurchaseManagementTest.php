<?php

namespace Tests\Feature;

use App\Enums\PackagingType;
use App\Enums\PaymentMethod;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierAndPurchaseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->assignRole('SuperAdmin');
        $this->actingAs($this->adminUser);
    }

    public function test_suppliers_index_renders_successfully(): void
    {
        $response = $this->get(route('suppliers.index'));
        $response->assertStatus(200);
    }

    public function test_supplier_can_be_created_with_opening_due(): void
    {
        $response = $this->post(route('suppliers.store'), [
            'name' => 'Akbar Ali',
            'company_name' => 'Bengal Bakery Products Ltd.',
            'phone' => '01711223344',
            'email' => 'akbar@bengalbakery.com',
            'opening_due' => 25000.00,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Akbar Ali',
            'company_name' => 'Bengal Bakery Products Ltd.',
            'opening_due' => 25000.00,
            'current_due' => 25000.00,
        ]);
    }

    public function test_supplier_ledger_page_renders_successfully(): void
    {
        $supplier = Supplier::create([
            'name' => 'Pran Foods',
            'phone' => '01811223344',
            'opening_due' => 10000.00,
            'current_due' => 10000.00,
        ]);

        $response = $this->get(route('suppliers.show', $supplier));
        $response->assertStatus(200);
    }

    public function test_creating_purchase_increments_warehouse_stock_and_logs_purchase_in_movement(): void
    {
        $supplier = Supplier::create([
            'name' => 'Olympic Industries',
            'phone' => '01911223344',
            'opening_due' => 0.00,
            'current_due' => 0.00,
        ]);

        $product = Product::create([
            'name' => 'Energy Plus Biscuit',
            'code' => 'EN-01',
            'packaging_type' => PackagingType::Cartoon->value,
            'cost_price' => 50.00,
            'wholesale_price' => 65.00,
            'warehouse_stock' => 20.00,
            'min_alert_quantity' => 10,
        ]);

        $response = $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'discount' => 100.00,
            'paid_amount' => 500.00,
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [
                [
                    'product_id' => $product->id,
                    'packaging_type' => 'CARTOON',
                    'quantity' => 50.00,
                    'unit_cost' => 50.00, // 50 * 50 = 2500
                ],
            ],
        ]);

        $response->assertRedirect(route('purchases.index'));
        $response->assertSessionHas('success');

        // Subtotal = 2500, Discount = 100 -> Grand Total = 2400.
        // Paid = 500 -> Due = 1900.
        $this->assertDatabaseHas('purchases', [
            'supplier_id' => $supplier->id,
            'subtotal' => 2500.00,
            'grand_total' => 2400.00,
            'paid_amount' => 500.00,
            'due_amount' => 1900.00,
        ]);

        // Warehouse stock incremented: 20 + 50 = 70
        $this->assertEquals(70.00, (float) $product->fresh()->warehouse_stock);

        // Immutable audit movement logged
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => StockMovementType::PurchaseIn->value,
            'quantity' => 50.00,
            'balance_before' => 20.00,
            'balance_after' => 70.00,
        ]);

        // Supplier due balance increased by 1900
        $this->assertEquals(1900.00, (float) $supplier->fresh()->current_due);
    }

    public function test_supplier_payment_reduces_supplier_current_due(): void
    {
        $supplier = Supplier::create([
            'name' => 'Danish Foods Ltd.',
            'phone' => '01511223344',
            'opening_due' => 15000.00,
            'current_due' => 15000.00,
        ]);

        $response = $this->post(route('suppliers.payments.store'), [
            'supplier_id' => $supplier->id,
            'payment_method' => PaymentMethod::BankTransfer->value,
            'amount' => 5000.00,
            'payment_date' => now()->toDateString(),
            'transaction_ref' => 'EFT-99887711',
            'notes' => 'Weekly partial payment',
        ]);

        $response->assertSessionHas('success');

        // Supplier current due reduced from 15000 to 10000
        $this->assertEquals(10000.00, (float) $supplier->fresh()->current_due);

        $this->assertDatabaseHas('supplier_payments', [
            'supplier_id' => $supplier->id,
            'amount' => 5000.00,
            'payment_method' => 'BANK_TRANSFER',
        ]);
    }

    public function test_supplier_with_purchases_cannot_be_deleted(): void
    {
        $supplier = Supplier::create([
            'name' => 'Pran Agro',
            'phone' => '01611223344',
        ]);

        Purchase::create([
            'purchase_no' => 'PUR-TEST-001',
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'grand_total' => 1000,
        ]);

        $response = $this->delete(route('suppliers.destroy', $supplier));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_user_can_view_purchase_invoice_details(): void
    {
        $supplier = Supplier::create([
            'name' => 'Akij Bakers Ltd.',
            'phone' => '01711998877',
        ]);

        $purchase = Purchase::create([
            'purchase_no' => 'PUR-VIEW-001',
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'subtotal' => 5000.00,
            'grand_total' => 5000.00,
            'paid_amount' => 5000.00,
            'due_amount' => 0.00,
            'status' => 'RECEIVED',
        ]);

        $response = $this->get(route('purchases.show', $purchase));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Purchases/Show')
            ->has('purchase')
            ->where('purchase.id', $purchase->id)
            ->where('purchase.purchase_no', 'PUR-VIEW-001')
        );
    }
}
