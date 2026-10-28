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
    Route::resource('roles', RoleController::class)->except(['create', 'show', 'edit']);
    Route::resource('users', UserController::class)->except(['create', 'show', 'edit']);
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    // Phase 3: Roads & Retailers (Tier 3)
    Route::resource('roads', RoadController::class)->except(['create', 'show', 'edit']);
    Route::resource('retailers', RetailerController::class)->except(['create', 'edit']);

    // Phase 4: Product Packaging, Catalog & Central Warehouse Stock (Tier 2)
    Route::post('products/categories', [ProductController::class, 'storeCategory'])->name('products.categories.store');
    Route::put('products/categories/{category}', [ProductController::class, 'updateCategory'])->name('products.categories.update');
    Route::delete('products/categories/{category}', [ProductController::class, 'destroyCategory'])->name('products.categories.destroy');

    Route::post('products/packaging-types', [PackagingTypeController::class, 'store'])->name('products.packaging-types.store');
    Route::put('products/packaging-types/{packagingType}', [PackagingTypeController::class, 'update'])->name('products.packaging-types.update');
    Route::delete('products/packaging-types/{packagingType}', [PackagingTypeController::class, 'destroy'])->name('products.packaging-types.destroy');

    Route::post('products/units', [UnitController::class, 'store'])->name('products.units.store');
    Route::put('products/units/{unit}', [UnitController::class, 'update'])->name('products.units.update');
    Route::delete('products/units/{unit}', [UnitController::class, 'destroy'])->name('products.units.destroy');

    Route::resource('products', ProductController::class)->except(['create', 'show', 'edit']);

    Route::get('stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('stock/movements', [StockController::class, 'movements'])->name('stock.movements');

    Route::get('stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
    Route::post('stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');

    // Phase 5: Supplier Procurement & Purchase Stock-In Engine (Tier 1 Inflow)
    Route::post('suppliers/payments', [SupplierController::class, 'recordPayment'])->name('suppliers.payments.store');
    Route::resource('suppliers', SupplierController::class)->except(['create', 'edit']);
    Route::get('purchases/create', fn () => redirect()->route('purchases.index', array_merge(['action' => 'create'], request()->query())))->name('purchases.create');
    Route::resource('purchases', PurchaseController::class)->only(['index', 'store', 'show']);

    // Phase 6: Vehicle Mobile Stock Loading & Trip Management (Mominul Bekari -> Road)
    Route::patch('trips/{trip}/dispatch', [VehicleTripController::class, 'dispatch'])->name('trips.dispatch');
    Route::get('trips/create', fn () => redirect()->route('trips.index', array_merge(['action' => 'create'], request()->query())))->name('trips.create');
    Route::resource('trips', VehicleTripController::class)->only(['index', 'store', 'show']);

    // Phase 7: Sales Memo, Billing & Credit (Due) Engine (Road -> Retailer Delivery)
    Route::post('memos/collect-due', [MemoController::class, 'collectDue'])->name('memos.collect-due');
    Route::get('memos/create', fn () => redirect()->route('memos.index', array_merge(['action' => 'create'], request()->query())))->name('memos.create');
    Route::resource('memos', MemoController::class)->only(['index', 'store', 'show']);

    // Phase 8: Returns & Exchanges (Damage Returns & Product Exchanges)
    Route::get('damage-returns', [DamageReturnController::class, 'index'])->name('damage-returns.index');
    Route::post('damage-returns/claims', [DamageReturnController::class, 'storeClaim'])->name('damage-returns.claims.store');

    // Phase 9: Road-Wise Sales, Collections & Stock Reconciliation
    Route::get('trips/{trip}/reconcile', [TripReconciliationController::class, 'show'])->name('trips.reconcile');
    Route::post('trips/{trip}/reconcile', [TripReconciliationController::class, 'store'])->name('trips.reconcile.store');
    Route::get('reports/road-settlement', [RoadReportController::class, 'index'])->name('reports.road-settlement');

    // Phase 10: Operational Expenses, Settings, DB Backup & Polish
    Route::resource('expenses', ExpenseController::class)->only(['index', 'store', 'destroy']);
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('backup', [BackupController::class, 'index'])->name('backup.index');
    Route::post('backup', [BackupController::class, 'store'])->name('backup.store');
    Route::get('backup/{backup}/download', [BackupController::class, 'download'])->name('backup.download');
    Route::delete('backup/{backup}', [BackupController::class, 'destroy'])->name('backup.destroy');

    // Profile Management
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});
