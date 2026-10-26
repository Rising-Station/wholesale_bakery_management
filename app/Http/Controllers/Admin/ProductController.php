<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PackagingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductCategoryRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductCategoryRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Services\ProductService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * Display a listing of products and packaging specifications.
     */
    public function index(Request $request): Response
    {
        $categoryId = $request->input('category_id') ? (int) $request->input('category_id') : null;
        $search = $request->input('search');
        $packagingType = $request->input('packaging_type');

        $products = $this->productService->getFilteredProducts($categoryId, $search, $packagingType);
        $categories = $this->productService->getAllCategories();

        $dbPackagingTypes = \App\Models\PackagingType::where('is_active', true)->orderBy('name')->get();
        $allDbPackagingTypes = \App\Models\PackagingType::orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('name')->get();
        $allUnits = Unit::orderBy('name')->get();

        $enumPackagingTypes = array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'short_label' => $case->shortLabel(),
        ], PackagingType::cases());

        $customTypesMapped = $dbPackagingTypes->map(fn ($pt) => [
            'value' => $pt->code,
            'label' => $pt->name,
            'short_label' => $pt->short_label ?? $pt->name,
            'id' => $pt->id,
        ])->toArray();

        // Merge existing enum cases with custom DB packaging types
        $mergedValues = array_column($enumPackagingTypes, 'value');
        foreach ($customTypesMapped as $ctm) {
            if (! in_array($ctm['value'], $mergedValues)) {
                $enumPackagingTypes[] = $ctm;
            }
        }

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'packagingTypes' => $enumPackagingTypes,
            'allPackagingTypes' => $allDbPackagingTypes,
            'units' => $units,
            'allUnits' => $allUnits,
            'filters' => [
                'category_id' => $categoryId,
                'search' => $search,
                'packaging_type' => $packagingType,
            ],
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->productService->createProduct($request->validated());

        return redirect()->route('products.index')->with('success', "Product '{$product->name}' created successfully.");
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->productService->updateProduct($product, $request->validated());

        return redirect()->route('products.index')->with('success', "Product '{$product->name}' updated successfully.");
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): RedirectResponse
    {
        try {
            $this->productService->deleteProduct($product);

            return redirect()->route('products.index')->with('success', "Product '{$product->name}' deleted successfully.");
        } catch (Exception $e) {
            return redirect()->route('products.index')->with('error', $e->getMessage());
        }
    }

    /**
     * Store a new category directly from the product view modal.
     */
    public function storeCategory(StoreProductCategoryRequest $request): RedirectResponse
    {
        $category = $this->productService->createCategory($request->validated());

        return redirect()->route('products.index')->with('success', "Category '{$category->name}' created successfully.");
    }

    /**
     * Update an existing category.
     */
    public function updateCategory(UpdateProductCategoryRequest $request, ProductCategory $category): RedirectResponse
    {
        $this->productService->updateCategory($category, $request->validated());

        return redirect()->route('products.index')->with('success', "Category '{$category->name}' updated successfully.");
    }

    /**
     * Delete a category.
     */
    public function destroyCategory(ProductCategory $category): RedirectResponse
    {
        try {
            $this->productService->deleteCategory($category);

            return redirect()->route('products.index')->with('success', "Category '{$category->name}' deleted successfully.");
        } catch (Exception $e) {
            return redirect()->route('products.index')->with('error', $e->getMessage());
        }
    }
}
