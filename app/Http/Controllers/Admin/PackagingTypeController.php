<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StorePackagingTypeRequest;
use App\Http\Requests\Product\UpdatePackagingTypeRequest;
use App\Models\PackagingType;
use Exception;
use Illuminate\Http\RedirectResponse;

class PackagingTypeController extends Controller
{
    /**
     * Store a new packaging type.
     */
    public function store(StorePackagingTypeRequest $request): RedirectResponse
    {
        $packagingType = PackagingType::create($request->validated());

        return redirect()->route('products.index')->with('success', "Packaging type '{$packagingType->name}' created successfully.");
    }

    /**
     * Update an existing packaging type.
     */
    public function update(UpdatePackagingTypeRequest $request, PackagingType $packagingType): RedirectResponse
    {
        $packagingType->update($request->validated());

        return redirect()->route('products.index')->with('success', "Packaging type '{$packagingType->name}' updated successfully.");
    }

    /**
     * Remove a packaging type.
     */
    public function destroy(PackagingType $packagingType): RedirectResponse
    {
        try {
            if ($packagingType->products()->exists()) {
                throw new Exception("Packaging type '{$packagingType->name}' is linked to existing products and cannot be deleted.");
            }

            $packagingType->delete();

            return redirect()->route('products.index')->with('success', "Packaging type '{$packagingType->name}' deleted successfully.");
        } catch (Exception $e) {
            return redirect()->route('products.index')->with('error', $e->getMessage());
        }
    }
}
