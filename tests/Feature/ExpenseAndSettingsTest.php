<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\DatabaseBackup;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Road;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\ExpenseAndSettingsSeeder;
use Database\Seeders\RoadAndRetailerSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(RoadAndRetailerSeeder::class);
        $this->seed(ExpenseAndSettingsSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('SuperAdmin');
        $this->actingAs($this->admin);
    }

    public function test_user_can_view_expenses_index_and_filter_by_category(): void
    {
        $category = ExpenseCategory::first();

        Expense::create([
            'expense_category_id' => $category->id,
            'expense_date' => now()->toDateString(),
            'amount' => 1250.00,
            'payment_method' => PaymentMethod::Cash->value,
            'notes' => 'Diesel fuel refill for Road A vehicle',
        ]);

        $response = $this->get(route('expenses.index', ['category_id' => $category->id]));
        $response->assertOk();
    }

    public function test_user_can_record_operational_expense(): void
    {
        Storage::fake('public');
        $category = ExpenseCategory::first();
        $road = Road::first();
        $file = UploadedFile::fake()->image('fuel_receipt.jpg');

        $response = $this->post(route('expenses.store'), [
            'expense_category_id' => $category->id,
            'road_id' => $road->id,
            'vehicle_number' => 'DHK-METRO-11-2034',
            'expense_date' => now()->toDateString(),
            'amount' => 850.00,
            'payment_method' => PaymentMethod::Cash->value,
            'receipt_number' => 'PETROL-9988',
            'receipt_attachment' => $file,
            'notes' => 'Route A fuel voucher',
        ]);

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('expenses', [
            'expense_category_id' => $category->id,
            'road_id' => $road->id,
            'amount' => 850.00,
            'receipt_number' => 'PETROL-9988',
        ]);
    }

    public function test_user_can_delete_expense(): void
    {
        $category = ExpenseCategory::first();

        $expense = Expense::create([
            'expense_category_id' => $category->id,
            'expense_date' => now()->toDateString(),
            'amount' => 300.00,
            'payment_method' => PaymentMethod::Cash->value,
            'notes' => 'Tea for drivers',
        ]);

        $response = $this->delete(route('expenses.destroy', $expense));
        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_user_can_view_settings_and_update_settings(): void
    {
        $response = $this->get(route('settings.index'));
        $response->assertOk();

        $response = $this->put(route('settings.update'), [
            'bakery_name' => 'Mominul Bekari Hub & Central Wholesale',
            'bakery_phone' => '+880 1711-999999',
            'bakery_email' => 'contact@mominulbekari.com',
            'bakery_address' => 'Tejgaon Industrial Area, Dhaka',
            'currency_symbol' => '৳',
            'receipt_header' => 'Mominul Bekari Best Quality',
            'receipt_footer' => 'Call 01711-999999 for orders',
            'low_stock_threshold_default' => 25,
            'sms_alerts_enabled' => '1',
            'enable_credit_limit_warnings' => '1',
        ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('success');

        $this->assertEquals('Mominul Bekari Hub & Central Wholesale', Setting::get('bakery_name'));
        $this->assertEquals('+880 1711-999999', Setting::get('bakery_phone'));
        $this->assertEquals('1', Setting::get('sms_alerts_enabled'));
    }

    public function test_user_can_update_demo_login_setting(): void
    {
        $response = $this->put(route('settings.update'), [
            'bakery_name' => 'Mominul Bekari Hub',
            'bakery_phone' => '+880 1711-999999',
            'bakery_email' => 'contact@mominulbekari.com',
            'bakery_address' => 'Tejgaon Industrial Area, Dhaka',
            'currency_symbol' => '৳',
            'receipt_header' => 'Mominul Bekari Best Quality',
            'receipt_footer' => 'Call 01711-999999 for orders',
            'low_stock_threshold_default' => 20,
            'sms_alerts_enabled' => '0',
            'enable_credit_limit_warnings' => '1',
            'enable_demo_login' => '0',
        ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('success');

        $this->assertEquals('0', Setting::get('enable_demo_login'));
    }

    public function test_user_can_create_database_backup_snapshot(): void
    {
        $response = $this->post(route('backup.store'));
        $response->assertRedirect(route('backup.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('database_backups', 1);

        $backup = DatabaseBackup::first();
        $this->assertNotNull($backup);

        $downloadResponse = $this->get(route('backup.download', $backup));
        $this->assertTrue(in_array($downloadResponse->getStatusCode(), [200, 302]));

        $deleteResponse = $this->delete(route('backup.destroy', $backup));
        $deleteResponse->assertRedirect(route('backup.index'));
        $this->assertDatabaseCount('database_backups', 0);
    }

    public function test_user_can_view_landing_page(): void
    {
        $response = $this->get(route('welcome'));
        $response->assertOk();
    }
}
