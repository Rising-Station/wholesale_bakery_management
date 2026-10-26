<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierPaymentRequest;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Models\Supplier;
use App\Services\SupplierService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService
    ) {}

    /**
     * Display a listing of suppliers (Tier 1 manufacturers).
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $suppliers = $this->supplierService->getFilteredSuppliers($search);
        $totalSupplierDue = Supplier::where('is_active', true)->sum('current_due');

        return Inertia::render('Admin/Suppliers/Index', [
            'suppliers' => $suppliers,
            'metrics' => [
                'total_supplier_due' => (float) $totalSupplierDue,
            ],
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = $this->supplierService->createSupplier($request->validated());

        return redirect()->route('suppliers.index')
            ->with('success', "Supplier '{$supplier->name}' registered successfully.");
    }

    /**
     * Display the specified supplier ledger and profile.
     */
    public function show(Supplier $supplier): Response
    {
        $supplier = $this->supplierService->getSupplierLedger($supplier);
        $paymentMethods = array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], PaymentMethod::cases());

        return Inertia::render('Admin/Suppliers/Show', [
            'supplier' => $supplier,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Update the specified supplier.
     */
    public function update(StoreSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->supplierService->updateSupplier($supplier, $request->validated());

        return redirect()->route('suppliers.index')
            ->with('success', "Supplier '{$supplier->name}' updated successfully.");
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        try {
            $this->supplierService->deleteSupplier($supplier);

            return redirect()->route('suppliers.index')
                ->with('success', 'Supplier removed successfully.');
        } catch (Exception $e) {
            return redirect()->route('suppliers.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Record a disbursement payment against supplier due balance.
     */
    public function recordPayment(StoreSupplierPaymentRequest $request): RedirectResponse
    {
        try {
            $this->supplierService->recordPayment($request->validated());

            return back()->with('success', 'Payment to supplier recorded successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
