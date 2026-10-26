<?php

namespace App\Services;

use App\Models\RetailerPayment;
use App\Models\RetailerShop;
use App\Models\Road;
use App\Models\SalesMemo;
use App\Models\VehicleTrip;
use App\Models\VehicleTripItem;
use Carbon\Carbon;

class RoadReportService
{
    /**
     * Generate road-wise performance and settlement report.
     *
     * @return array<string, mixed>
     */
    public function getRoadSettlementReport(?string $dateFrom = null, ?string $dateTo = null, ?int $selectedRoadId = null): array
    {
        $startDate = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : Carbon::today()->startOfDay();
        $endDate = $dateTo ? Carbon::parse($dateTo)->endOfDay() : Carbon::today()->endOfDay();

        $roads = Road::where('is_active', true)->orderBy('code')->get();

        $roadReports = [];
        $consolidated = [
            'total_trips' => 0,
            'completed_trips' => 0,
            'total_loaded_qty' => 0.0,
            'total_sold_qty' => 0.0,
            'total_damaged_qty' => 0.0,
            'total_exchanged_qty' => 0.0,
            'total_returned_qty' => 0.0,
            'memos_count' => 0,
            'gross_sales' => 0.0,
            'damage_deductions' => 0.0,
            'exchange_deductions' => 0.0,
            'custom_deductions' => 0.0,
            'net_sales' => 0.0,
            'cash_collected' => 0.0,
            'digital_collected' => 0.0,
            'total_collected' => 0.0,
            'dues_generated' => 0.0,
            'active_retailers_count' => 0,
            'total_current_due' => 0.0,
        ];

        foreach ($roads as $road) {
            $trips = VehicleTrip::where('road_id', $road->id)
                ->whereBetween('trip_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->with(['items.product', 'memos', 'payments'])
                ->get();

            $tripIds = $trips->pluck('id')->all();

            $memos = SalesMemo::where('road_id', $road->id)
                ->whereBetween('memo_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get();

            $payments = RetailerPayment::where('road_id', $road->id)
                ->whereBetween('payment_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get();

            $retailers = RetailerShop::where('road_id', $road->id)->where('is_active', true)->get();
            $totalRoadDue = (float) $retailers->sum('current_due');

            $tripItems = VehicleTripItem::whereIn('vehicle_trip_id', $tripIds)->get();

            $loadedQty = (float) $tripItems->sum('loaded_quantity');
            $soldQty = (float) $tripItems->sum('sold_quantity');
            $damagedQty = (float) $tripItems->sum('damaged_quantity');
            $exchangedQty = (float) $tripItems->sum('exchanged_quantity');
            $returnedQty = (float) $tripItems->sum('returned_quantity');

            $grossSales = (float) $memos->sum('subtotal');
            $damageDeductions = (float) $memos->sum('damage_deduction');
            $exchangeDeductions = (float) $memos->sum('exchange_deduction');
            $customDeductions = (float) $memos->sum('custom_deduction');
            $netSales = (float) $memos->sum('grand_total');
            $duesGenerated = (float) $memos->sum('due_amount');

            $cashCollected = (float) $payments->filter(fn ($p) => $p->payment_method->value === 'CASH')->sum('amount');
            $digitalCollected = (float) $payments->filter(fn ($p) => $p->payment_method->value !== 'CASH')->sum('amount');
            $totalCollected = (float) $payments->sum('amount');

            $reportData = [
                'road' => $road,
                'total_trips' => $trips->count(),
                'completed_trips' => $trips->where('status.value', 'COMPLETED')->count(),
                'total_loaded_qty' => $loadedQty,
                'total_sold_qty' => $soldQty,
                'total_damaged_qty' => $damagedQty,
                'total_exchanged_qty' => $exchangedQty,
                'total_returned_qty' => $returnedQty,
                'memos_count' => $memos->count(),
                'gross_sales' => $grossSales,
                'damage_deductions' => $damageDeductions,
                'exchange_deductions' => $exchangeDeductions,
                'custom_deductions' => $customDeductions,
                'net_sales' => $netSales,
                'cash_collected' => $cashCollected,
                'digital_collected' => $digitalCollected,
                'total_collected' => $totalCollected,
                'dues_generated' => $duesGenerated,
                'active_retailers_count' => $retailers->count(),
                'total_current_due' => $totalRoadDue,
                'trips_list' => $trips,
            ];

            $roadReports[$road->id] = $reportData;

            // Aggregate into consolidated totals
            $consolidated['total_trips'] += $trips->count();
            $consolidated['completed_trips'] += $trips->where('status.value', 'COMPLETED')->count();
            $consolidated['total_loaded_qty'] += $loadedQty;
            $consolidated['total_sold_qty'] += $soldQty;
            $consolidated['total_damaged_qty'] += $damagedQty;
            $consolidated['total_exchanged_qty'] += $exchangedQty;
            $consolidated['total_returned_qty'] += $returnedQty;
            $consolidated['memos_count'] += $memos->count();
            $consolidated['gross_sales'] += $grossSales;
            $consolidated['damage_deductions'] += $damageDeductions;
            $consolidated['exchange_deductions'] += $exchangeDeductions;
            $consolidated['custom_deductions'] += $customDeductions;
            $consolidated['net_sales'] += $netSales;
            $consolidated['cash_collected'] += $cashCollected;
            $consolidated['digital_collected'] += $digitalCollected;
            $consolidated['total_collected'] += $totalCollected;
            $consolidated['dues_generated'] += $duesGenerated;
            $consolidated['active_retailers_count'] += $retailers->count();
            $consolidated['total_current_due'] += $totalRoadDue;
        }

        return [
            'date_from' => $startDate->toDateString(),
            'date_to' => $endDate->toDateString(),
            'roads' => $roads,
            'road_reports' => $roadReports,
            'consolidated' => $consolidated,
            'selected_road_id' => $selectedRoadId,
        ];
    }
}
