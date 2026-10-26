<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {}

    /**
     * Display a listing of purchase invoices (Stock-In to Mominul Bekari).
     */
    public function index(Request $request): Response
    {
        $supplierId = $request->input('supplier_id') ? (int) $request->input('supplier_id') : null;
        $search = $request->input('search');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $purchases = $this->purchaseService->getFilteredPurchases($supplierId, $search, $dateFrom, $dateTo);
        $suppliers = Supplier::where('is_active', true)->select('id', 'name', 'company_name', 'current_due')->orderBy('name')->get();
        $products = Product::where('is_active', true)->select('id', 'name', 'code', 'cost_price', 'packaging_type')->orderBy('name')->get();

        $paymentMethods = array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], PaymentMethod::cases());

        return Inertia::render('Admin/Purchases/Index', [
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'products' => $products,
            'paymentMethods' => $paymentMethods,
            'filters' => [
                'supplier_id' => $supplierId,
                'search' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * Store a newly created purchase invoice and increment central warehouse stock.
     */
    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        try {
            $purchase = $this->purchaseService->createPurchase($request->validated());

            return redirect()->route('purchases.index')
                ->with('success', "Purchase invoice '{$purchase->purchase_no}' received and central warehouse stock incremented successfully.");
        } catch (Exception $e) {
            return redirect()->route('purchases.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display printable purchase invoice.
     */
    public function show(Purchase $purchase): Response
    {
        $purchaseDetails = $this->purchaseService->getPurchaseWithDetails($purchase);

        return Inertia::render('Admin/Purchases/Show', [
            'purchase' => $purchaseDetails,
        ]);
    }
}
