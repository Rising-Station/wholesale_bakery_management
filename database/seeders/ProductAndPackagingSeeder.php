<?php

namespace Database\Seeders;

use App\Enums\PackagingType as PackagingTypeEnum;
use App\Enums\PurchaseStatus;
use App\Enums\StockLocationType;
use App\Enums\StockMovementType;
use App\Models\PackagingType;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductAndPackagingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Units
        $units = [
            ['name' => 'Cartoon', 'short_code' => 'ctn', 'is_active' => true],
            ['name' => 'Packet', 'short_code' => 'pkt', 'is_active' => true],
            ['name' => 'Piece', 'short_code' => 'pcs', 'is_active' => true],
            ['name' => 'Kilogram', 'short_code' => 'kg', 'is_active' => true],
            ['name' => 'Gram', 'short_code' => 'gm', 'is_active' => true],
        ];

        $unitModels = [];
        foreach ($units as $u) {
            $unitModels[$u['short_code']] = Unit::firstOrCreate(
                ['short_code' => $u['short_code']],
                $u
            );
        }

        // 2. Packaging Types
        $packagingTypes = [
            [
                'name' => 'Cartoon (Master Box)',
                'code' => 'CARTOON',
                'short_label' => 'CTN',
                'description' => 'Master corrugated cartoon box for wholesale transit',
                'is_active' => true,
            ],
            [
                'name' => '5 KG Bulk Packet',
                'code' => 'PACKET_5KG',
                'short_label' => '5KG',
                'description' => '5 kg bulk sealed poly pack for wholesale chanachur & bulk items',
                'is_active' => true,
            ],
            [
                'name' => '12 Pcs Packet',
                'code' => 'PACKET_12PCS',
                'short_label' => '12PC',
                'description' => '12 individual piece retail card / packet',
                'is_active' => true,
            ],
            [
                'name' => 'Single Piece / Loose',
                'code' => 'PIECES',
                'short_label' => 'PCS',
                'description' => 'Single retail unit / individual piece',
                'is_active' => true,
            ],
            [
                'name' => 'Custom Bulk Container',
                'code' => 'CUSTOM',
                'short_label' => 'CUST',
                'description' => 'Custom packaging arrangement',
                'is_active' => true,
            ],
        ];

        $pkgModels = [];
        foreach ($packagingTypes as $pt) {
            $pkgModels[$pt['code']] = PackagingType::firstOrCreate(
                ['code' => $pt['code']],
                $pt
            );
        }

        // 3. Categories: Cake and Chanachur
        $cakeCategory = ProductCategory::firstOrCreate(
            ['slug' => 'cake'],
            [
                'name' => 'Cake',
                'description' => 'Freshly baked wholesale cakes, fruit cakes, and vanilla cakes',
                'is_active' => true,
            ]
        );

        $chanachurCategory = ProductCategory::firstOrCreate(
            ['slug' => 'chanachur'],
            [
                'name' => 'Chanachur',
                'description' => 'Crispy wholesale chanachur and savouries packed in bulk packets',
                'is_active' => true,
            ]
        );

        // 4. Supplier
        $adminUser = User::first();
        $supplier = Supplier::firstOrCreate(
            ['phone' => '01711223344'],
            [
                'name' => 'Al-Madina Bakery & Foods Supplier',
                'company_name' => 'Al-Madina Agro & Food Industries',
                'email' => 'supplier.madina@bakeryfoods.com',
                'address' => 'Tejgaon Industrial Area, Dhaka',
                'opening_due' => 0.00,
                'current_due' => 0.00,
                'is_active' => true,
                'created_by' => $adminUser?->id,
            ]
        );

        // 5. Products
        // Product 1: Cake - 100 cartoon
        $cakeProduct = Product::firstOrCreate(
            ['code' => 'CAKE-CTN-01'],
            [
                'category_id' => $cakeCategory->id,
                'unit_id' => $unitModels['ctn']->id,
                'packaging_type_id' => $pkgModels['CARTOON']->id,
                'name' => 'Special Plain Cake',
                'packaging_type' => PackagingTypeEnum::Cartoon,
                'units_per_cartoon' => 24,
                'pieces_per_packet' => null,
                'weight_per_unit_kg' => null,
                'cost_price' => 350.00,
                'wholesale_price' => 450.00,
                'warehouse_stock' => 100.00,
                'min_alert_quantity' => 15,
                'is_active' => true,
                'notes' => 'Master box contains 24 standard pieces of vanilla plain cake',
            ]
        );

        // Product 2: Chanachur 5kg packet 100 qty
        $chanachurProduct = Product::firstOrCreate(
            ['code' => 'CHN-5KG-01'],
            [
                'category_id' => $chanachurCategory->id,
                'unit_id' => $unitModels['pkt']->id,
                'packaging_type_id' => $pkgModels['PACKET_5KG']->id,
                'name' => 'Spicy Bombay Chanachur (5kg Packet)',
                'packaging_type' => PackagingTypeEnum::Packet5kg,
                'units_per_cartoon' => 1,
                'pieces_per_packet' => 1,
                'weight_per_unit_kg' => 5.000,
                'cost_price' => 700.00,
                'wholesale_price' => 850.00,
                'warehouse_stock' => 100.00,
                'min_alert_quantity' => 10,
                'is_active' => true,
                'notes' => 'Heavy duty sealed 5kg bulk packet for wholesale retailers',
            ]
        );

        // 6. Ensure Purchase Bill and StockMovement exist for traceability
        $purchase = Purchase::firstOrCreate(
            ['purchase_no' => 'PUR-INIT-0001'],
            [
                'supplier_id' => $supplier->id,
                'purchase_date' => now()->toDateString(),
                'subtotal' => 105000.00,
                'discount' => 0.00,
                'grand_total' => 105000.00,
                'paid_amount' => 105000.00,
                'due_amount' => 0.00,
                'status' => PurchaseStatus::Received,
                'notes' => 'Initial inventory stock-in purchase bill',
                'created_by' => $adminUser?->id,
            ]
        );

        // Cake purchase item
        PurchaseItem::firstOrCreate(
            [
                'purchase_id' => $purchase->id,
                'product_id' => $cakeProduct->id,
            ],
            [
                'packaging_type' => PackagingTypeEnum::Cartoon,
                'quantity' => 100.00,
                'unit_cost' => 350.00,
                'line_total' => 35000.00,
            ]
        );

        // Chanachur purchase item
        PurchaseItem::firstOrCreate(
            [
                'purchase_id' => $purchase->id,
                'product_id' => $chanachurProduct->id,
            ],
            [
                'packaging_type' => PackagingTypeEnum::Packet5kg,
                'quantity' => 100.00,
                'unit_cost' => 700.00,
                'line_total' => 70000.00,
            ]
        );

        // Stock Movement 1: Cake 100 cartoons
        StockMovement::firstOrCreate(
            [
                'product_id' => $cakeProduct->id,
                'reference_type' => 'Purchase',
                'reference_id' => $purchase->id,
            ],
            [
                'movement_type' => StockMovementType::PurchaseIn,
                'source_location' => StockLocationType::CentralWarehouse,
                'destination_location' => StockLocationType::CentralWarehouse,
                'quantity' => 100.00,
                'balance_before' => 0.00,
                'balance_after' => 100.00,
                'remarks' => 'Initial received purchase stock-in: 100 Cartoons',
                'created_by' => $adminUser?->id,
            ]
        );

        // Stock Movement 2: Chanachur 100 packets (5kg each)
        StockMovement::firstOrCreate(
            [
                'product_id' => $chanachurProduct->id,
                'reference_type' => 'Purchase',
                'reference_id' => $purchase->id,
            ],
            [
                'movement_type' => StockMovementType::PurchaseIn,
                'source_location' => StockLocationType::CentralWarehouse,
                'destination_location' => StockLocationType::CentralWarehouse,
                'quantity' => 100.00,
                'balance_before' => 0.00,
                'balance_after' => 100.00,
                'remarks' => 'Initial received purchase stock-in: 100 Packets (5kg each)',
                'created_by' => $adminUser?->id,
            ]
        );
    }
}
