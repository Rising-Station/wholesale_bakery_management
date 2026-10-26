<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Display central warehouse inventory stock overview and low-stock indicators.
     */
    public function index(Request $request): Response
    {
        $categoryId = $request->input('category_id') ? (int) $request->input('category_id') : null;
        $search = $request->input('search');
        $lowStockOnly = $request->boolean('low_stock');

        $products = $this->stockService->getWarehouseStockOverview($categoryId, $search, $lowStockOnly);
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $lowStockCount = Product::lowStock()->count();

        // Calculate total central warehouse stock valuation
        $totalCostValuation = Product::active()
            ->sum(DB::raw('warehouse_stock * cost_price'));
        $totalWholesaleValuation = Product::active()
            ->sum(DB::raw('warehouse_stock * wholesale_price'));
        $totalQuantityInStock = Product::active()->sum('warehouse_stock');

        return Inertia::render('Admin/Stock/Index', [
            'products' => $products,
            'categories' => $categories,
            'metrics' => [
                'low_stock_count' => $lowStockCount,
                'total_cost_valuation' => (float) $totalCostValuation,
                'total_wholesale_valuation' => (float) $totalWholesaleValuation,
                'total_quantity' => (float) $totalQuantityInStock,
            ],
            'filters' => [
                'category_id' => $categoryId,
                'search' => $search,
                'low_stock' => $lowStockOnly,
            ],
        ]);
    }

    /**
     * Display the immutable stock movement audit ledger.
     */
    public function movements(Request $request): Response
    {
        $productId = $request->input('product_id') ? (int) $request->input('product_id') : null;
        $movementType = $request->input('movement_type');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $movements = $this->stockService->getStockMovements($productId, $movementType, $dateFrom, $dateTo);
        $products = Product::where('is_active', true)->select('id', 'name', 'code')->orderBy('name')->get();

        $movementTypes = array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], StockMovementType::cases());

        return Inertia::render('Admin/Stock/Movements', [
            'movements' => $movements,
            'products' => $products,
            'movementTypes' => $movementTypes,
            'filters' => [
                'product_id' => $productId,
                'movement_type' => $movementType,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }
}
