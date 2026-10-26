<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\ReturnType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Memo\StoreMemoRequest;
use App\Http\Requests\Retailer\CollectRetailerDuePaymentRequest;
use App\Models\Product;
use App\Models\RetailerShop;
use App\Models\Road;
use App\Models\SalesMemo;
use App\Models\VehicleTrip;
use App\Services\MemoService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemoController extends Controller
{
    public function __construct(
        protected MemoService $memoService
    ) {}

    /**
     * Display a listing of sales memos and bills.
     */
    public function index(Request $request): Response
    {
        $roadId = $request->input('road_id') ? (int) $request->input('road_id') : null;
        $retailerId = $request->input('retailer_id') ? (int) $request->input('retailer_id') : null;
        $search = $request->input('search');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $memos = $this->memoService->getFilteredMemos($roadId, $retailerId, $search, $dateFrom, $dateTo);
        $roads = Road::where('is_active', true)->select('id', 'name', 'code')->orderBy('code')->get();
        $retailers = RetailerShop::where('is_active', true)->select('id', 'road_id', 'shop_name', 'owner_name', 'phone', 'current_due')->orderBy('shop_name')->get();
        $trips = VehicleTrip::whereIn('status', ['LOADED', 'DISPATCHED'])->with('items.product')->latest('id')->get();
        $products = Product::where('is_active', true)->select('id', 'name', 'code', 'wholesale_price', 'warehouse_stock', 'packaging_type')->orderBy('name')->get();

        $paymentMethods = array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], PaymentMethod::cases());

        $returnTypes = array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], ReturnType::cases());

        return Inertia::render('Admin/Memos/Index', [
            'memos' => $memos,
            'roads' => $roads,
            'retailers' => $retailers,
            'trips' => $trips,
            'products' => $products,
            'paymentMethods' => $paymentMethods,
            'returnTypes' => $returnTypes,
            'filters' => [
                'road_id' => $roadId,
                'retailer_id' => $retailerId,
                'search' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * Store a newly created sales memo.
     */
    public function store(StoreMemoRequest $request): RedirectResponse
    {
        try {
            $memo = $this->memoService->createMemo($request->validated());

            return redirect()->route('memos.show', $memo)
                ->with('success', "Sales memo '{$memo->memo_no}' created and balance updated successfully.");
        } catch (Exception $e) {
            return redirect()->route('memos.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display printable Sales Memo (Thermal 80mm & Standard A4 formats).
     */
    public function show(SalesMemo $memo): Response
    {
        $memoDetails = $this->memoService->getMemoWithDetails($memo);

        return Inertia::render('Admin/Memos/Show', [
            'memo' => $memoDetails,
        ]);
    }

    /**
     * Direct retailer due payment collection modal handler.
     */
    public function collectDue(CollectRetailerDuePaymentRequest $request): RedirectResponse
    {
        try {
            $this->memoService->collectDuePayment($request->validated());

            return back()->with('success', 'Retailer due payment collected and balance updated successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
