<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreStockAdjustmentRequest;
use App\Models\Product;
use App\Services\StockService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StockAdjustmentController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Display a listing of physical audit stock adjustments.
     */
    public function index(): Response
    {
        $adjustments = $this->stockService->getAdjustments();
        $products = Product::where('is_active', true)
            ->select('id', 'name', 'code', 'warehouse_stock', 'packaging_type')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Stock/Adjustments', [
            'adjustments' => $adjustments,
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created physical stock count audit adjustment.
     */
    public function store(StoreStockAdjustmentRequest $request): RedirectResponse
    {
        try {
            $adjustment = $this->stockService->processAdjustment($request->validated());

            return redirect()->route('stock-adjustments.index')
                ->with('success', "Stock Adjustment '{$adjustment->adjustment_no}' recorded and inventory balances updated successfully.");
        } catch (Exception $e) {
            return redirect()->route('stock-adjustments.index')
                ->with('error', $e->getMessage());
        }
    }
}
