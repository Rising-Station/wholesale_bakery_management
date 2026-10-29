<?php

namespace App\Services;

use App\Models\RetailerShop;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RetailerService
{
    /**
     * Get filtered retailer shops with road relation.
     */
    public function getFilteredRetailers(?int $roadId = null, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        return RetailerShop::with(['road:id,name,code'])
            ->when($roadId, function ($query, $roadId): void {
                $query->where('road_id', $roadId);
            })
            ->when($search, function ($query, $search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('shop_name', 'like', "%{$search}%")
                        ->orWhere('owner_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create a new retailer shop.
     *
     * @param  array<string, mixed>  $data
     */
    public function createRetailer(array $data): RetailerShop
    {
        return DB::transaction(function () use ($data) {
            $openingDue = (float) ($data['opening_due'] ?? 0);
            $data['current_due'] = $openingDue;

            return RetailerShop::create($data);
        });
    }

    /**
     * Update an existing retailer shop.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateRetailer(RetailerShop $shop, array $data): RetailerShop
    {
        $shop->update($data);

        return $shop;
    }

    /**
     * Delete a retailer shop if it has no sales memos.
     *
     * @throws Exception
     */
    public function deleteRetailer(RetailerShop $shop): void
    {
        $shop->delete();
    }
}
