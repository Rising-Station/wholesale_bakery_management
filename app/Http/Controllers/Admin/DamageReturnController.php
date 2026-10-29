<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\ReturnType;
use App\Http\Controllers\Controller;
use App\Models\DamageReturn;
use App\Models\DamageScrapInventory;
use App\Models\Product;
use App\Models\RetailerShop;
use App\Models\Road;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\SupplierReturnClaim;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DamageReturnController extends Controller
{
    /**
     * Display a listing and audit report of damage returns and exchanges.
     */
    public function index(Request $request): Response
    {
        $returnType = $request->input('return_type');
        $roadId = $request->input('road_id') ? (int) $request->input('road_id') : null;
        $retailerId = $request->input('retailer_id') ? (int) $request->input('retailer_id') : null;
        $productId = $request->input('product_id') ? (int) $request->input('product_id') : null;
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = DamageReturn::with(['retailer', 'product', 'salesMemo'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($returnType) {
            $query->where('return_type', $returnType);
        }

        if ($roadId) {
            $query->whereHas('retailer', function ($q) use ($roadId): void {
                $q->where('road_id', $roadId);
            });
        }

        if ($retailerId) {
            $query->where('retailer_id', $retailerId);
        }

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $returns = $query->paginate(25)->withQueryString();

        $totalDamageDeductions = DamageReturn::where('return_type', ReturnType::Damage->value)->sum('total_deduction_amount');
        $totalExchangeDeductions = DamageReturn::where('return_type', ReturnType::Exchange->value)->sum('total_deduction_amount');
        $quarantineScrapCount = DamageScrapInventory::where('status', 'QUARANTINED')->sum('quantity');
        $repackHoldingCount = DamageScrapInventory::where('category', 'REPACK_HOLDING')->sum('quantity');
        $repackHoldingWeight = DamageScrapInventory::where('category', 'REPACK_HOLDING')->sum('weight_kg');
        $repackHoldingPieces = DamageScrapInventory::where('category', 'REPACK_HOLDING')->sum('pieces_count');

        $roads = Road::where('is_active', true)->select('id', 'name', 'code')->get();
        $retailers = RetailerShop::where('is_active', true)->select('id', 'shop_name')->get();
        $products = Product::where('is_active', true)->select('id', 'name', 'code', 'weight_per_unit_kg', 'units_per_cartoon', 'pieces_per_packet', 'packaging_type', 'wholesale_price', 'cost_price')->get();
        $suppliers = Supplier::where('is_active', true)->select('id', 'name', 'company_name', 'current_due')->get();

        $claims = SupplierReturnClaim::with(['supplier:id,name,company_name,current_due', 'product:id,name,code,packaging_type', 'creator:id,name', 'approver:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'claims_page')
            ->withQueryString();

        return Inertia::render('Admin/Returns/Index', [
            'returns' => $returns,
            'claims' => $claims,
            'roads' => $roads,
            'retailers' => $retailers,
            'products' => $products,
            'suppliers' => $suppliers,
            'metrics' => [
                'total_damage_deductions' => (float) $totalDamageDeductions,
                'total_exchange_deductions' => (float) $totalExchangeDeductions,
                'quarantine_scrap_quantity' => (float) $quarantineScrapCount,
                'repack_holding_quantity' => (float) $repackHoldingCount,
                'repack_holding_weight' => (float) $repackHoldingWeight,
                'repack_holding_pieces' => (int) $repackHoldingPieces,
                'pending_claims_count' => (int) SupplierReturnClaim::where('status', 'PENDING')->count(),
                'approved_claims_total' => (float) SupplierReturnClaim::where('status', 'APPROVED')->sum('claim_amount'),
            ],
            'filters' => [
                'return_type' => $returnType,
                'road_id' => $roadId,
                'retailer_id' => $retailerId,
                'product_id' => $productId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * File a Supplier Return Claim for quarantined damage/expired products.
     */
    public function storeClaim(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $claimNo = 'CLM-'.date('Ymd').'-'.rand(1000, 9999);
            $claimAmount = round($validated['quantity'] * $validated['unit_cost'], 2);

            SupplierReturnClaim::create([
                'claim_no' => $claimNo,
                'supplier_id' => $validated['supplier_id'],
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'unit_cost' => $validated['unit_cost'],
                'claim_amount' => $claimAmount,
                'reason' => $validated['reason'],
                'status' => 'PENDING',
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            return back()->with('success', "Supplier return claim '{$claimNo}' submitted successfully.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Approve a Supplier Return Claim and deduct the claim amount from Supplier Due (Credit Note).
     */
    public function approveClaim(SupplierReturnClaim $claim): RedirectResponse
    {
        if ($claim->status !== 'PENDING') {
            return back()->with('error', "This claim is already {$claim->status}.");
        }

        try {
            DB::transaction(function () use ($claim): void {
                $user = Auth::id();

                // 1. Update claim status
                $claim->update([
                    'status' => 'APPROVED',
                    'approved_by' => $user,
                    'settled_at' => now(),
                ]);

                // 2. Deduct claim amount from Supplier Current Due
                /** @var Supplier $supplier */
                $supplier = Supplier::lockForUpdate()->findOrFail($claim->supplier_id);
                $supplier->current_due = max(0, round((float) $supplier->current_due - (float) $claim->claim_amount, 2));
                $supplier->save();

                // 3. Log credit payment transaction in supplier_payments
                SupplierPayment::create([
                    'supplier_id' => $supplier->id,
                    'payment_method' => PaymentMethod::ReturnAdjustment->value,
                    'amount' => $claim->claim_amount,
                    'payment_date' => now()->toDateString(),
                    'transaction_ref' => $claim->claim_no,
                    'notes' => "Damage Return Claim Credit Note: {$claim->quantity} units of {$claim->product?->name} ({$claim->reason})",
                    'created_by' => $user,
                ]);

                // 4. Clear quarantined scrap inventory units
                $remaining = (float) $claim->quantity;
                $quarantined = DamageScrapInventory::where('product_id', $claim->product_id)
                    ->where('status', 'QUARANTINED')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                foreach ($quarantined as $item) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $itemQty = (float) $item->quantity;
                    if ($itemQty <= $remaining) {
                        $remaining -= $itemQty;
                        $item->update([
                            'status' => 'RETURNED_TO_SUPPLIER',
                            'disposal_date' => now()->toDateString(),
                            'disposal_notes' => "Returned to supplier {$supplier->name} under claim #{$claim->claim_no}",
                        ]);
                    } else {
                        $clearedQty = $remaining;
                        $item->decrement('quantity', $clearedQty);

                        DamageScrapInventory::create([
                            'product_id' => $item->product_id,
                            'retailer_id' => $item->retailer_id,
                            'road_id' => $item->road_id,
                            'damage_type' => $item->damage_type,
                            'category' => $item->category,
                            'quantity' => $clearedQty,
                            'status' => 'RETURNED_TO_SUPPLIER',
                            'disposal_date' => now()->toDateString(),
                            'disposal_notes' => "Returned to supplier {$supplier->name} under claim #{$claim->claim_no}",
                            'created_by' => $user,
                        ]);
                        $remaining = 0;
                    }
                }
            });

            return back()->with('success', "Claim '{$claim->claim_no}' approved! ৳ ".number_format((float) $claim->claim_amount, 2)." deducted from {$claim->supplier?->name}'s due balance, and quarantined scrap cleared.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject a Supplier Return Claim.
     */
    public function rejectClaim(SupplierReturnClaim $claim, Request $request): RedirectResponse
    {
        if ($claim->status !== 'PENDING') {
            return back()->with('error', "This claim is already {$claim->status}.");
        }

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $notes = $claim->notes ? $claim->notes."\n" : '';
        $notes .= 'Rejected: '.($validated['rejection_reason'] ?? 'Factory refused claim.');

        $claim->update([
            'status' => 'REJECTED',
            'notes' => $notes,
            'approved_by' => Auth::id(),
            'settled_at' => now(),
        ]);

        return back()->with('info', "Claim '{$claim->claim_no}' marked as REJECTED.");
    }
}
