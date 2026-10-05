<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Road\StoreRoadRequest;
use App\Models\Road;
use App\Models\User;
use App\Services\RoadService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoadController extends Controller
{
    public function __construct(
        protected RoadService $roadService
    ) {}

    /**
     * Display a listing of distribution roads (Road A, B, C).
     */
    public function index(): Response
    {
        $roads = $this->roadService->getAllWithStats();
        $drivers = User::role('SalesDriver')->where('is_active', true)->select('id', 'name', 'phone')->get();

        return Inertia::render('Admin/Roads/Index', [
            'roads' => $roads,
            'drivers' => $drivers,
        ]);
    }

    /**
     * Store a newly created road.
     */
    public function store(StoreRoadRequest $request): RedirectResponse
    {
        $road = $this->roadService->createRoad($request->validated());

        return redirect()->route('roads.index')->with('success', "Road '{$road->name}' created successfully.");
    }

    /**
     * Update the specified road.
     */
    public function update(StoreRoadRequest $request, Road $road): RedirectResponse
    {
        $this->roadService->updateRoad($road, $request->validated());

        return redirect()->route('roads.index')->with('success', "Road '{$road->name}' updated successfully.");
    }

    /**
     * Remove the specified road.
     */
    public function destroy(Road $road): RedirectResponse
    {
        try {
            $this->roadService->deleteRoad($road);

            return redirect()->route('roads.index')->with('success', 'Road deleted successfully.');
        } catch (Exception $e) {
            return redirect()->route('roads.index')->with('error', $e->getMessage());
        }
    }
}
