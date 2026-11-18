<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseCategoryRequest;
use App\Http\Requests\Expense\UpdateExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExpenseCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            $categories = ExpenseCategory::withCount('expenses')->orderBy('name')->get();

            return response()->json($categories);
        }

        return redirect()->route('expenses.index');
    }

    /**
     * Store a newly created expense category.
     */
    public function store(StoreExpenseCategoryRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            if (empty($data['code'])) {
                $baseCode = strtoupper(Str::slug($data['name'], '_'));
                $code = $baseCode;
                $counter = 1;

                while (ExpenseCategory::where('code', $code)->exists()) {
                    $code = "{$baseCode}_{$counter}";
                    $counter++;
                }

                $data['code'] = $code;
            } else {
                $data['code'] = strtoupper($data['code']);
            }

            if (! isset($data['is_active'])) {
                $data['is_active'] = true;
            }

            $category = ExpenseCategory::create($data);

            return redirect()->route('expenses.index')->with('success', "Expense category '{$category->name}' created successfully.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update the specified expense category.
     */
    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $category): RedirectResponse
    {
        try {
            $data = $request->validated();

            if (! empty($data['code'])) {
                $data['code'] = strtoupper($data['code']);
            }

            if (isset($data['is_active'])) {
                $data['is_active'] = (bool) $data['is_active'];
            }

            $category->update($data);

            return redirect()->route('expenses.index')->with('success', "Expense category '{$category->name}' updated successfully.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified expense category.
     */
    public function destroy(ExpenseCategory $category): RedirectResponse
    {
        try {
            if ($category->expenses()->exists()) {
                throw new Exception("Expense category '{$category->name}' has linked expense records and cannot be deleted.");
            }

            $category->delete();

            return redirect()->route('expenses.index')->with('success', "Expense category '{$category->name}' deleted successfully.");
        } catch (Exception $e) {
            return redirect()->route('expenses.index')->with('error', $e->getMessage());
        }
    }
}
