<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Retailer\StoreRetailerRequest;
use App\Http\Requests\Retailer\UpdateRetailerRequest;
use App\Models\RetailerInquiry;
use App\Models\RetailerShop;
use App\Models\Road;
use App\Services\RetailerService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RetailerController extends Controller
{
    public function __construct(
        protected RetailerService $retailerService
    ) {}

    /**
     * Display a listing of retailer shops grouped by road.
     */
    public function index(Request $request): Response
    {
        $roadId = $request->input('road_id') ? (int) $request->input('road_id') : null;
        $search = $request->input('search');

        $retailers = $this->retailerService->getFilteredRetailers($roadId, $search);
        $roads = Road::where('is_active', true)->select('id', 'name', 'code')->orderBy('code')->get();

        return Inertia::render('Admin/Retailers/Index', [
            'retailers' => $retailers,
            'roads' => $roads,
            'inquiries' => RetailerInquiry::with('road:id,name,code')->latest()->take(30)->get(),
            'filters' => [
                'road_id' => $roadId,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a newly registered retailer shop.
     */
    public function store(StoreRetailerRequest $request): RedirectResponse
    {
        $shop = $this->retailerService->createRetailer($request->validated());

        return redirect()->route('retailers.index')->with('success', "Retailer shop '{$shop->shop_name}' registered successfully.");
    }

    /**
     * Display the specified retailer shop profile and ledger.
     */
    public function show(RetailerShop $retailer): Response
    {
        $retailer->load(['road', 'creator:id,name']);

        return Inertia::render('Admin/Retailers/Show', [
            'retailer' => $retailer,
        ]);
    }

    /**
     * Update the specified retailer shop.
     */
    public function update(UpdateRetailerRequest $request, RetailerShop $retailer): RedirectResponse
    {
        $this->retailerService->updateRetailer($retailer, $request->validated());

        return redirect()->route('retailers.index')->with('success', "Retailer shop '{$retailer->shop_name}' updated successfully.");
    }

    /**
     * Remove the specified retailer shop.
     */
    public function destroy(RetailerShop $retailer): RedirectResponse
    {
        try {
            $this->retailerService->deleteRetailer($retailer);

            return redirect()->route('retailers.index')->with('success', 'Retailer shop removed successfully.');
        } catch (Exception $e) {
            return redirect()->route('retailers.index')->with('error', $e->getMessage());
        }
    }
}
