<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trip\StoreTripRequest;
use App\Models\Product;
use App\Models\Road;
use App\Models\User;
use App\Models\VehicleTrip;
use App\Services\TripService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleTripController extends Controller
{
    public function __construct(
        protected TripService $tripService
    ) {}

    /**
     * Display a listing of vehicle mobile trips.
     */
    public function index(Request $request): Response
    {
        $roadId = $request->input('road_id') ? (int) $request->input('road_id') : null;
        $status = $request->input('status');
        $date = $request->input('date');

        $trips = $this->tripService->getFilteredTrips($roadId, $status, $date);
        $roads = Road::where('is_active', true)->select('id', 'name', 'code', 'vehicle_number', 'driver_name', 'driver_phone', 'driver_id')->orderBy('code')->get();
        $drivers = User::role('SalesDriver')->where('is_active', true)->select('id', 'name', 'phone')->get();
        $products = Product::where('is_active', true)->where('warehouse_stock', '>', 0)->select('id', 'name', 'code', 'warehouse_stock', 'packaging_type')->orderBy('name')->get();

        return Inertia::render('Admin/Trips/Index', [
            'trips' => $trips,
            'roads' => $roads,
            'drivers' => $drivers,
            'products' => $products,
            'filters' => [
                'road_id' => $roadId,
                'status' => $status,
                'date' => $date,
            ],
        ]);
    }

    /**
     * Store a newly created vehicle loading sheet and transfer stock.
     */
    public function store(StoreTripRequest $request): RedirectResponse
    {
        try {
            $trip = $this->tripService->createTrip($request->validated());

            return redirect()->route('trips.index')
                ->with('success', "Trip '{$trip->trip_no}' created and vehicle stock loaded successfully.");
        } catch (Exception $e) {
            return redirect()->route('trips.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the vehicle loading manifest and printable gate pass.
     */
    public function show(VehicleTrip $trip): Response
    {
        $manifest = $this->tripService->getTripManifest($trip);

        return Inertia::render('Admin/Trips/Show', [
            'trip' => $manifest,
        ]);
    }

    /**
     * Mark vehicle trip as dispatched.
     */
    public function dispatch(VehicleTrip $trip): RedirectResponse
    {
        $this->tripService->dispatchTrip($trip);

        return back()->with('success', "Vehicle for Trip '{$trip->trip_no}' dispatched on road.");
    }
}
