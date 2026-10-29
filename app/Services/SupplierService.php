<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    /**
     * Get paginated filtered suppliers.
     */
    public function getFilteredSuppliers(?string $search = null): LengthAwarePaginator
    {
        $query = Supplier::withCount('purchases')
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->paginate(20)->withQueryString();
    }

    /**
     * Create a new supplier.
     */
    public function createSupplier(array $data): Supplier
    {
        $openingDue = (float) ($data['opening_due'] ?? 0.00);
        $data['opening_due'] = $openingDue;
        $data['current_due'] = $openingDue;

        return Supplier::create($data);
    }

    /**
     * Update an existing supplier.
     */
    public function updateSupplier(Supplier $supplier, array $data): Supplier
    {
        unset($data['current_due'], $data['opening_due']);
        $supplier->update($data);

        return $supplier;
    }

    /**
     * Delete supplier if no purchases or payments exist.
     */
    public function deleteSupplier(Supplier $supplier): void
    {
        if ($supplier->purchases()->exists() || $supplier->payments()->exists()) {
            throw new Exception("Supplier '{$supplier->name}' has existing purchase or payment records and cannot be deleted. Set to inactive instead.");
        }

        $supplier->delete();
    }

    /**
     * Record a disbursement payment to a supplier.
     */
    public function recordPayment(array $data, ?int $userId = null): SupplierPayment
    {
        return DB::transaction(function () use ($data, $userId): SupplierPayment {
            $user = $userId ?? Auth::id();
            /** @var Supplier $supplier */
            $supplier = Supplier::lockForUpdate()->findOrFail($data['supplier_id']);
            $amount = (float) $data['amount'];

            $payment = SupplierPayment::create([
                'supplier_id' => $supplier->id,
                'purchase_id' => $data['purchase_id'] ?? null,
                'payment_method' => $data['payment_method'],
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'transaction_ref' => $data['transaction_ref'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user,
            ]);

            // If applied to a specific purchase, adjust purchase paid and due amounts
            if (! empty($data['purchase_id'])) {
                /** @var Purchase $purchase */
                $purchase = Purchase::lockForUpdate()->find($data['purchase_id']);
                if ($purchase) {
                    $purchase->paid_amount += $amount;
                    $purchase->due_amount = max(0, $purchase->grand_total - $purchase->paid_amount);
                    $purchase->save();
                }
            }

            // Atomically decrement supplier current due balance
            $supplier->current_due = max(0, (float) $supplier->current_due - $amount);
            $supplier->save();

            return $payment;
        });
    }

    /**
     * Get full supplier profile and transaction history ledger.
     */
    public function getSupplierLedger(Supplier $supplier): Supplier
    {
        return $supplier->load([
            'purchases' => fn ($q) => $q->orderByDesc('purchase_date')->orderByDesc('id')->with('items.product'),
            'payments' => fn ($q) => $q->orderByDesc('payment_date')->orderByDesc('id'),
            'creator:id,name',
        ]);
    }
}
