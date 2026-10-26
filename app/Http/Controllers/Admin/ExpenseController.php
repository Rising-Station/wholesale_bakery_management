<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Road;
use App\Traits\FileUploadTrait;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    use FileUploadTrait;

    /**
     * Display a listing of operational expenses.
     */
    public function index(Request $request): Response
    {
        $categoryId = $request->input('category_id') ? (int) $request->input('category_id') : null;
        $roadId = $request->input('road_id') ? (int) $request->input('road_id') : null;
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Expense::with(['category', 'road', 'creator:id,name'])
            ->latest('expense_date');

        if ($categoryId) {
            $query->where('expense_category_id', $categoryId);
        }

        if ($roadId) {
            $query->where('road_id', $roadId);
        }

        if ($dateFrom) {
            $query->whereDate('expense_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('expense_date', '<=', $dateTo);
        }

        $totalAmount = (float) (clone $query)->sum('amount');
        $expenses = $query->paginate(20)->withQueryString();

        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        $roads = Road::where('is_active', true)->select('id', 'name', 'code', 'vehicle_number')->orderBy('code')->get();

        $paymentMethods = array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], PaymentMethod::cases());

        return Inertia::render('Admin/Expenses/Index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'roads' => $roads,
            'paymentMethods' => $paymentMethods,
            'totalAmount' => $totalAmount,
            'filters' => [
                'category_id' => $categoryId,
                'road_id' => $roadId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * Store a newly created operational expense in storage.
     */
    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('receipt_attachment')) {
                $data['receipt_attachment'] = $this->uploadFile($request->file('receipt_attachment'), 'expenses');
            }

            Expense::create($data);

            return redirect()->route('expenses.index')
                ->with('success', 'Expense recorded successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified expense from storage.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        try {
            if ($expense->receipt_attachment) {
                $this->deleteFile($expense->receipt_attachment);
            }

            $expense->delete();

            return redirect()->route('expenses.index')
                ->with('success', 'Expense deleted successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
