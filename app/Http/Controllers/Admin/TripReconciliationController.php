<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trip\ReconcileTripRequest;
use App\Models\VehicleTrip;
use App\Services\TripService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TripReconciliationController extends Controller
{
    public function __construct(
        protected TripService $tripService
    ) {}

    /**
     * Show the trip reconciliation and closing sheet.
     */
    public function show(VehicleTrip $trip): Response
    {
        $data = $this->tripService->getTripForReconciliation($trip);

        return Inertia::render('Admin/Trips/Reconcile', [
            'trip' => $data['trip'],
            'items' => $data['items'],
            'summary' => $data['summary'],
        ]);
    }

    /**
     * Complete reconciliation: accept returned stock and close trip.
     */
    public function store(ReconcileTripRequest $request, VehicleTrip $trip): RedirectResponse
    {
        try {
            $this->tripService->completeReconciliation($trip, $request->validated());

            return redirect()->route('trips.index')
                ->with('success', "Trip '{$trip->trip_no}' successfully reconciled, unsold items returned to Central Warehouse, and trip closed.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
