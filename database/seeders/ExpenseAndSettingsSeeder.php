<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class ExpenseAndSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Vehicle Fuel (Diesel / Octane / CNG)', 'code' => 'FUEL', 'description' => 'Route delivery vehicle fuel expenses'],
            ['name' => 'Vehicle Repair & Maintenance', 'code' => 'MAINTENANCE', 'description' => 'Servicing, tyre repair, mobile oil and parts'],
            ['name' => 'Depot & Warehouse Rent', 'code' => 'RENT', 'description' => 'Central warehouse and hub depot monthly rent'],
            ['name' => 'Staff Salary & Daily Allowances', 'code' => 'SALARY', 'description' => 'Driver wages, helper DA, and bakery employee salaries'],
            ['name' => 'Electricity, Gas & Water Utilities', 'code' => 'UTILITIES', 'description' => 'Factory power bills and municipal utilities'],
            ['name' => 'Packaging Materials & Crates', 'code' => 'PACKAGING', 'description' => 'Cartons, poly bags, and distribution crates'],
            ['name' => 'Staff Tea & Entertainment', 'code' => 'ENTERTAINMENT', 'description' => 'Daily refreshment and retail partner hosting'],
            ['name' => 'Miscellaneous Overhead', 'code' => 'MISC', 'description' => 'General petty cash expenses'],
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::updateOrCreate(['code' => $cat['code']], $cat);
        }

        $defaultSettings = [
            'bakery_name' => 'Mominul Bekari Wholesale Distribution',
            'bakery_phone' => '+880 1711-000004',
            'bakery_email' => 'sales@mominulbekari.com',
            'bakery_address' => 'Central Distribution Hub, Commercial Road Zone, Dhaka',
            'currency_symbol' => '৳',
            'receipt_header' => 'Mominul Bekari - Quality Wholesale Bakery Goods',
            'receipt_footer' => 'Thank you for your business! Freshly baked daily. For orders: 01711-000004',
            'low_stock_threshold_default' => '20',
            'sms_alerts_enabled' => '0',
            'enable_credit_limit_warnings' => '1',
            'enable_demo_login' => '1',
            // Public Site CMS Content
            'hero_badge' => 'Delivering Fresh Bakery Goods Daily Across Distribution Corridors',
            'hero_title' => 'Reliable Wholesale Bakery Distribution For Modern Retailers',
            'hero_subtitle' => 'Connecting manufacturing producers directly with road-side grocery and tea stall retailers through dedicated vehicle fleets, transparent dual-ledger dues, and instant mobile billing.',
            'tier1_title' => 'Suppliers & Procurement',
            'tier1_description' => 'Producers supply bulk freshly baked biscuits, bread, buns, and cakes directly into central warehouse inventory with batch tracking and supplier due management.',
            'tier2_title' => 'Bakery Hub & Vehicle Fleet',
            'tier2_description' => 'Daily loading sheets transfer warehouse stock to route vehicles. Gate passes are generated and vehicles dispatched with mobile stock invariants.',
            'tier3_title' => 'Road Retailer Shops',
            'tier3_description' => 'Drivers issue instant sales memos with transparent live balance calculation, collect cash/MFS, deduct damage returns into quarantine, and exchange slow goods for fresh stock.',
            'partnership_title' => 'Join Our Retail Distribution Route',
            'partnership_subtitle' => 'Run a grocery store, tea shop, or bakery retail counter? Sign up to receive daily fresh stock delivery.',
        ];

        foreach ($defaultSettings as $key => $val) {
            Setting::set($key, $val, 'general');
        }
    }
}
