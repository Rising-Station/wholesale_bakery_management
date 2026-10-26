<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreUnitRequest;
use App\Http\Requests\Product\UpdateUnitRequest;
use App\Models\Unit;
use Exception;
use Illuminate\Http\RedirectResponse;

class UnitController extends Controller
{
    /**
     * Store a new unit.
     */
    public function store(StoreUnitRequest $request): RedirectResponse
    {
        $unit = Unit::create($request->validated());

        return redirect()->route('products.index')->with('success', "Unit '{$unit->name}' created successfully.");
    }

    /**
     * Update an existing unit.
     */
    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return redirect()->route('products.index')->with('success', "Unit '{$unit->name}' updated successfully.");
    }

    /**
     * Remove a unit.
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        try {
            if ($unit->products()->exists()) {
                throw new Exception("Unit '{$unit->name}' is linked to existing products and cannot be deleted.");
            }

            $unit->delete();

            return redirect()->route('products.index')->with('success', "Unit '{$unit->name}' deleted successfully.");
        } catch (Exception $e) {
            return redirect()->route('products.index')->with('error', $e->getMessage());
        }
    }
}
