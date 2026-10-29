<?php

use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\DamageReturnController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\MemoController;
use App\Http\Controllers\Admin\PackagingTypeController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\RetailerController;
use App\Http\Controllers\Admin\RoadController;
use App\Http\Controllers\Admin\RoadReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\TripReconciliationController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VehicleTripController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\RetailerPayment;
use App\Models\RetailerShop;
use App\Models\Road;
use App\Models\SalesMemo;
use App\Models\Supplier;
use App\Models\VehicleTrip;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Landing Page
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'products' => Product::where('is_active', true)->select('id', 'name', 'code', 'packaging_type', 'wholesale_price')->take(8)->get(),
        'roads' => Road::where('is_active', true)->select('id', 'name', 'code')->get(),
    ]);
})->name('welcome');

// Guest Authentication Routes
Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

// Authenticated Staff Routes
Route::middleware(['auth', 'active.user'])->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', function () {
        $lowStockCount = Product::lowStock()->count();
        $totalProducts = Product::count();
        $retailerDue = (float) RetailerShop::sum('current_due');
        $supplierDue = (float) Supplier::sum('current_due');
        $todaySales = (float) SalesMemo::whereDate('memo_date', today())->sum('grand_total');
        $todayMemoCollected = (float) SalesMemo::whereDate('memo_date', today())->sum('paid_amount');
        $todayDirectCollected = (float) RetailerPayment::whereDate('payment_date', today())->sum('amount');
        $activeTrips = VehicleTrip::whereIn('status', ['LOADED', 'DISPATCHED'])->count();

        $totalCostValuation = (float) Product::active()->sum(DB::raw('warehouse_stock * cost_price'));
        $totalWholesaleValuation = (float) Product::active()->sum(DB::raw('warehouse_stock * wholesale_price'));
        $totalStockUnits = (float) Product::active()->sum('warehouse_stock');
        $potentialProfit = $totalWholesaleValuation - $totalCostValuation;

        $categoryStock = ProductCategory::where('is_active', true)->with('products')->get()->map(function ($cat) {
            $cost = (float) $cat->products->where('is_active', true)->sum(fn ($p) => $p->warehouse_stock * $p->cost_price);
            $wholesale = (float) $cat->products->where('is_active', true)->sum(fn ($p) => $p->warehouse_stock * $p->wholesale_price);
            $units = (float) $cat->products->where('is_active', true)->sum('warehouse_stock');

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'products_count' => $cat->products->count(),
                'cost_value' => $cost,
                'wholesale_value' => $wholesale,
                'units' => $units,
            ];
        })->values();

        $topStockProducts = Product::active()
            ->with('category')
            ->where('warehouse_stock', '>', 0)
            ->orderByDesc(DB::raw('warehouse_stock * cost_price'))
            ->take(6)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'category_name' => $p->category?->name ?? 'Uncategorized',
                'warehouse_stock' => (float) $p->warehouse_stock,
                'cost_price' => (float) $p->cost_price,
                'wholesale_price' => (float) $p->wholesale_price,
                'cost_value' => (float) ($p->warehouse_stock * $p->cost_price),
                'wholesale_value' => (float) ($p->warehouse_stock * $p->wholesale_price),
            ]);

        $roads = Road::withCount('retailerShops')->where('is_active', true)->get()->map(fn ($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'code' => $r->code,
            'vehicle' => $r->vehicle_number ?? 'N/A',
            'driver' => $r->driver_name ?? 'Unassigned',
            'shops' => $r->retailer_shops_count,
            'loadedValue' => 0,
            'status' => 'ACTIVE',
        ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'todaySales' => $todaySales,
                'todayCollected' => $todayMemoCollected + $todayDirectCollected,
                'totalRetailerDue' => $retailerDue,
                'totalSupplierDue' => $supplierDue,
                'activeTripsCount' => $activeTrips,
                'lowStockItemsCount' => $lowStockCount,
                'totalProductsCount' => $totalProducts,
            ],
            'stockValuation' => [
                'total_cost_value' => $totalCostValuation,
                'total_wholesale_value' => $totalWholesaleValuation,
                'total_units' => $totalStockUnits,
                'potential_profit' => $potentialProfit,
                'categories' => $categoryStock,
                'top_products' => $topStockProducts,
            ],
            'roads' => $roads,
        ]);
    })->name('dashboard');

    // User & Role Management
    Route::middleware('can:roles.view')->get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::middleware('can:roles.manage')->group(function (): void {
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });

    Route::middleware('can:users.view')->get('users', [UserController::class, 'index'])->name('users.index');
    Route::middleware('can:users.create')->post('users', [UserController::class, 'store'])->name('users.store');
    Route::middleware('can:users.edit')->group(function (): void {
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    });
    Route::middleware('can:users.delete')->delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Phase 3: Roads & Retailers (Tier 3)
    Route::middleware('can:roads.view')->get('roads', [RoadController::class, 'index'])->name('roads.index');
    Route::middleware('can:roads.create')->post('roads', [RoadController::class, 'store'])->name('roads.store');
    Route::middleware('can:roads.edit')->put('roads/{road}', [RoadController::class, 'update'])->name('roads.update');
    Route::middleware('can:roads.delete')->delete('roads/{road}', [RoadController::class, 'destroy'])->name('roads.destroy');

    Route::middleware('can:retailers.view')->group(function (): void {
        Route::get('retailers', [RetailerController::class, 'index'])->name('retailers.index');
        Route::get('retailers/{retailer}', [RetailerController::class, 'show'])->name('retailers.show');
    });
    Route::middleware('can:retailers.create')->post('retailers', [RetailerController::class, 'store'])->name('retailers.store');
    Route::middleware('can:retailers.edit')->put('retailers/{retailer}', [RetailerController::class, 'update'])->name('retailers.update');
    Route::middleware('can:retailers.delete')->delete('retailers/{retailer}', [RetailerController::class, 'destroy'])->name('retailers.destroy');

    // Phase 4: Product Packaging, Catalog & Central Warehouse Stock (Tier 2)
    Route::middleware('can:products.view')->get('products', [ProductController::class, 'index'])->name('products.index');
    Route::middleware('can:products.create')->group(function (): void {
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::post('products/categories', [ProductController::class, 'storeCategory'])->name('products.categories.store');
        Route::post('products/packaging-types', [PackagingTypeController::class, 'store'])->name('products.packaging-types.store');
        Route::post('products/units', [UnitController::class, 'store'])->name('products.units.store');
    });
    Route::middleware('can:products.edit')->group(function (): void {
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::put('products/categories/{category}', [ProductController::class, 'updateCategory'])->name('products.categories.update');
        Route::put('products/packaging-types/{packagingType}', [PackagingTypeController::class, 'update'])->name('products.packaging-types.update');
        Route::put('products/units/{unit}', [UnitController::class, 'update'])->name('products.units.update');
    });
    Route::middleware('can:products.delete')->group(function (): void {
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::delete('products/categories/{category}', [ProductController::class, 'destroyCategory'])->name('products.categories.destroy');
        Route::delete('products/packaging-types/{packagingType}', [PackagingTypeController::class, 'destroy'])->name('products.packaging-types.destroy');
        Route::delete('products/units/{unit}', [UnitController::class, 'destroy'])->name('products.units.destroy');
    });

    Route::middleware('can:stock.view')->group(function (): void {
        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('stock/movements', [StockController::class, 'movements'])->name('stock.movements');
    });
    Route::middleware('can:stock.adjust')->group(function (): void {
        Route::get('stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
        Route::post('stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
    });

    // Phase 5: Supplier Procurement & Purchase Stock-In Engine (Tier 1 Inflow)
    Route::middleware('can:suppliers.view')->group(function (): void {
        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    });
    Route::middleware('can:suppliers.create')->post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::middleware('can:suppliers.edit')->put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::middleware('can:suppliers.delete')->delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    Route::middleware('can:suppliers.payment')->post('suppliers/payments', [SupplierController::class, 'recordPayment'])->name('suppliers.payments.store');

    Route::middleware('can:purchases.view')->group(function (): void {
        Route::get('purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('purchases/create', fn () => redirect()->route('purchases.index', array_merge(['action' => 'create'], request()->query())))->name('purchases.create');
        Route::get('purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
    });
    Route::middleware('can:purchases.create')->post('purchases', [PurchaseController::class, 'store'])->name('purchases.store');

    // Phase 6: Vehicle Mobile Stock Loading & Trip Management (Mominul Bekari -> Road)
    Route::middleware('can:trips.view')->group(function (): void {
        Route::get('trips', [VehicleTripController::class, 'index'])->name('trips.index');
        Route::get('trips/create', fn () => redirect()->route('trips.index', array_merge(['action' => 'create'], request()->query())))->name('trips.create');
        Route::get('trips/{trip}', [VehicleTripController::class, 'show'])->name('trips.show');
    });
    Route::middleware('can:trips.create')->post('trips', [VehicleTripController::class, 'store'])->name('trips.store');
    Route::middleware('can:trips.dispatch')->patch('trips/{trip}/dispatch', [VehicleTripController::class, 'dispatch'])->name('trips.dispatch');
    Route::middleware('can:trips.reconcile')->group(function (): void {
        Route::get('trips/{trip}/reconcile', [TripReconciliationController::class, 'show'])->name('trips.reconcile');
        Route::post('trips/{trip}/reconcile', [TripReconciliationController::class, 'store'])->name('trips.reconcile.store');
    });

    // Phase 7: Sales Memo, Billing & Credit (Due) Engine (Road -> Retailer Delivery)
    Route::middleware('can:memos.view')->group(function (): void {
        Route::get('memos', [MemoController::class, 'index'])->name('memos.index');
        Route::get('memos/create', fn () => redirect()->route('memos.index', array_merge(['action' => 'create'], request()->query())))->name('memos.create');
        Route::get('memos/{memo}', [MemoController::class, 'show'])->name('memos.show');
    });
    Route::middleware('can:memos.create')->post('memos', [MemoController::class, 'store'])->name('memos.store');
    Route::middleware('can:retailers.payment')->post('memos/collect-due', [MemoController::class, 'collectDue'])->name('memos.collect-due');

    // Phase 8: Returns & Exchanges (Damage Returns & Product Exchanges)
    Route::middleware('can:damage.view')->get('damage-returns', [DamageReturnController::class, 'index'])->name('damage-returns.index');
    Route::middleware('can:damage.create')->post('damage-returns/claims', [DamageReturnController::class, 'storeClaim'])->name('damage-returns.claims.store');
    Route::middleware('can:damage.claim')->group(function (): void {
        Route::post('damage-returns/claims/{claim}/approve', [DamageReturnController::class, 'approveClaim'])->name('damage-returns.claims.approve');
        Route::post('damage-returns/claims/{claim}/reject', [DamageReturnController::class, 'rejectClaim'])->name('damage-returns.claims.reject');
    });

    // Phase 9: Road-Wise Sales, Collections & Stock Reconciliation
    Route::middleware('can:reports.view')->get('reports/road-settlement', [RoadReportController::class, 'index'])->name('reports.road-settlement');

    // Phase 10: Operational Expenses, Settings, DB Backup & Polish
    Route::middleware('can:expenses.view')->get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::middleware('can:expenses.create')->post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::middleware('can:expenses.delete')->delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    Route::middleware('can:settings.manage')->group(function (): void {
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });

    Route::middleware('can:backup.manage')->group(function (): void {
        Route::get('backup', [BackupController::class, 'index'])->name('backup.index');
        Route::post('backup', [BackupController::class, 'store'])->name('backup.store');
        Route::get('backup/{backup}/download', [BackupController::class, 'download'])->name('backup.download');
        Route::delete('backup/{backup}', [BackupController::class, 'destroy'])->name('backup.destroy');
    });

    // Profile Management
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});
