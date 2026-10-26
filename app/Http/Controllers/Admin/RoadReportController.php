<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RoadReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoadReportController extends Controller
{
    public function __construct(
        protected RoadReportService $roadReportService
    ) {}

    /**
     * Display the Road-Wise Settlement & Operational Dashboard.
     */
    public function index(Request $request): Response
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $selectedRoadId = $request->input('road_id') ? (int) $request->input('road_id') : null;

        $report = $this->roadReportService->getRoadSettlementReport($dateFrom, $dateTo, $selectedRoadId);

        return Inertia::render('Admin/Reports/RoadSettlement', $report);
    }
}
