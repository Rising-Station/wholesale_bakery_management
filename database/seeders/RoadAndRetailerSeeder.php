<?php

namespace Database\Seeders;

use App\Models\RetailerShop;
use App\Models\Road;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoadAndRetailerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $driverUser = User::where('email', 'driver.a@mominulbekari.com')->first();

        // 1. Road A
        $roadA = Road::firstOrCreate(
            ['code' => 'RD-A'],
            [
                'name' => 'Road A (Main Commercial Road)',
                'vehicle_number' => 'DHK-METRO-11-2034',
                'driver_name' => 'Rafiqul Islam',
                'driver_phone' => '01711000004',
                'driver_id' => $driverUser?->id,
                'is_active' => true,
            ]
        );

        // 2. Road B
        $roadB = Road::firstOrCreate(
            ['code' => 'RD-B'],
            [
                'name' => 'Road B (East Bypass & Station Road)',
                'vehicle_number' => 'DHK-METRO-14-5512',
                'driver_name' => 'Anwar Hossain',
                'driver_phone' => '01711000012',
                'is_active' => true,
            ]
        );

        // 3. Road C
        $roadC = Road::firstOrCreate(
            ['code' => 'RD-C'],
            [
                'name' => 'Road C (North Upazila Link Road)',
                'vehicle_number' => 'DHK-METRO-19-8801',
                'driver_name' => 'Kabir Ahmed',
                'driver_phone' => '01711000013',
                'is_active' => true,
            ]
        );

        // Sample Retailer Shops for Road A
        $shopsA = [
            ['shop_name' => 'Al-Madina General Store', 'owner_name' => 'Haji Abdur Rahim', 'phone' => '01721112233', 'address' => 'Shop 12, Main Bazaar, Road A', 'credit_limit' => 20000, 'opening_due' => 4500],
            ['shop_name' => 'Bismillah Confectionery', 'owner_name' => 'Faruk Ahmed', 'phone' => '01819998877', 'address' => 'Near Central Mosque, Road A', 'credit_limit' => 15000, 'opening_due' => 2800],
            ['shop_name' => 'Popular Bakery & Tea Stall', 'owner_name' => 'Kamal Hossain', 'phone' => '01912345678', 'address' => 'Bus Stand Corner, Road A', 'credit_limit' => 10000, 'opening_due' => 0],
        ];

        foreach ($shopsA as $shop) {
            RetailerShop::firstOrCreate(
                ['shop_name' => $shop['shop_name'], 'road_id' => $roadA->id],
                [
                    'owner_name' => $shop['owner_name'],
                    'phone' => $shop['phone'],
                    'address' => $shop['address'],
                    'credit_limit' => $shop['credit_limit'],
                    'opening_due' => $shop['opening_due'],
                    'current_due' => $shop['opening_due'],
                    'is_active' => true,
                ]
            );
        }

        // Sample Retailer Shops for Road B
        $shopsB = [
            ['shop_name' => 'Rahman Grocery', 'owner_name' => 'Matiur Rahman', 'phone' => '01715554433', 'address' => 'Station Road Gate #2', 'credit_limit' => 12000, 'opening_due' => 1500],
            ['shop_name' => 'Sagorika Snacks Corner', 'owner_name' => 'Sohag Mia', 'phone' => '01612223344', 'address' => 'East Bypass Turning', 'credit_limit' => 8000, 'opening_due' => 3200],
        ];

        foreach ($shopsB as $shop) {
            RetailerShop::firstOrCreate(
                ['shop_name' => $shop['shop_name'], 'road_id' => $roadB->id],
                [
                    'owner_name' => $shop['owner_name'],
                    'phone' => $shop['phone'],
                    'address' => $shop['address'],
                    'credit_limit' => $shop['credit_limit'],
                    'opening_due' => $shop['opening_due'],
                    'current_due' => $shop['opening_due'],
                    'is_active' => true,
                ]
            );
        }

        // Sample Retailer Shops for Road C
        $shopsC = [
            ['shop_name' => 'Jannat Store', 'owner_name' => 'Abul Kashem', 'phone' => '01734445566', 'address' => 'North College Mor', 'credit_limit' => 25000, 'opening_due' => 6000],
        ];

        foreach ($shopsC as $shop) {
            RetailerShop::firstOrCreate(
                ['shop_name' => $shop['shop_name'], 'road_id' => $roadC->id],
                [
                    'owner_name' => $shop['owner_name'],
                    'phone' => $shop['phone'],
                    'address' => $shop['address'],
                    'credit_limit' => $shop['credit_limit'],
                    'opening_due' => $shop['opening_due'],
                    'current_due' => $shop['opening_due'],
                    'is_active' => true,
                ]
            );
        }
    }
}
