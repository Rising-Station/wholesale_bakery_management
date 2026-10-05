<?php

namespace App\Services;

use App\Models\Road;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class RoadService
{
    /**
     * Get all roads with retailer counts and driver details.
     *
     * @return Collection<int, Road>
     */
    public function getAllWithStats(): Collection
    {
        return Road::with(['driver:id,name,phone'])
            ->withCount(['retailerShops as total_shops', 'retailerShops as active_shops' => function ($query): void {
                $query->where('is_active', true);
            }])
            ->orderBy('code')
            ->get();
    }

    /**
     * Create a new delivery road route.
     *
     * @param  array<string, mixed>  $data
     */
    public function createRoad(array $data): Road
    {
        return Road::create($data);
    }

    /**
     * Update an existing road route.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateRoad(Road $road, array $data): Road
    {
        $road->update($data);

        return $road;
    }

    /**
     * Delete a road route if no retailer shops or vehicle trips exist.
     *
     * @throws Exception
     */
    public function deleteRoad(Road $road): void
    {
        if ($road->retailerShops()->count() > 0) {
            throw new Exception("Cannot delete Road '{$road->name}' because it contains registered retail shops.");
        }

        $road->delete();
    }
}
