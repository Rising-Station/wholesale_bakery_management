<?php

namespace App\Services;

use App\Enums\StockLocationType;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductCategory;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Get paginated filtered products.
     */
    public function getFilteredProducts(?int $categoryId = null, ?string $search = null, ?string $packagingType = null): LengthAwarePaginator
    {
        $query = Product::with(['category', 'unit', 'packagingTypeRelation'])
            ->orderBy('name');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($packagingType) {
            $query->where('packaging_type', $packagingType);
        }

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return $query->paginate(20)->withQueryString();
    }

    /**
     * Get all active categories.
     *
     * @return Collection<int, ProductCategory>
     */
    public function getAllCategories(): Collection
    {
        return ProductCategory::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Create a new product.
     */
    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $initialStock = (float) ($data['warehouse_stock'] ?? 0);
            $data['warehouse_stock'] = $initialStock;

            $product = Product::create($data);

            if ($initialStock > 0) {
                $this->stockService->recordMovement(
                    product: $product,
                    type: StockMovementType::AuditAdjustment,
                    source: StockLocationType::CentralWarehouse,
                    destination: StockLocationType::CentralWarehouse,
                    quantity: $initialStock,
                    balanceBefore: 0.00,
                    balanceAfter: $initialStock,
                    referenceType: 'InitialOpeningStock',
                    referenceId: $product->id,
                    remarks: 'Opening inventory recorded upon product creation',
                    userId: Auth::id()
                );
            }

            return $product;
        });
    }

    /**
     * Update an existing product.
     */
    public function updateProduct(Product $product, array $data): Product
    {
        // Prevent direct arbitrary stock tampering through basic edit form
        unset($data['warehouse_stock']);

        $product->update($data);

        return $product;
    }

    /**
     * Delete a product if it has no associated transaction history.
     */
    public function deleteProduct(Product $product): void
    {
        if ($product->stockMovements()->exists()) {
            throw new Exception("Product '{$product->name}' cannot be deleted because it has recorded stock movement transactions. Deactivate it instead.");
        }

        $product->delete();
    }

    /**
     * Create product category.
     */
    public function createCategory(array $data): ProductCategory
    {
        return ProductCategory::create($data);
    }

    /**
     * Update product category.
     */
    public function updateCategory(ProductCategory $category, array $data): ProductCategory
    {
        $category->update($data);

        return $category;
    }

    /**
     * Delete product category if no products attached.
     */
    public function deleteCategory(ProductCategory $category): void
    {
        if ($category->products()->exists()) {
            throw new Exception("Category '{$category->name}' contains registered products and cannot be deleted.");
        }

        $category->delete();
    }
}
