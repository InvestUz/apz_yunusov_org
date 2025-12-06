<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\PaymentSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ARTDashboardController extends Controller
{
    public function index(): View
    {
        // Calculate totals
        $totals = $this->calculateTotals();

        // Calculate per-region metrics
        $regions = $this->calculateRegionMetrics();

        return view('art.dashboard', compact('totals', 'regions'));
    }

    private function calculateTotals(): array
    {
        $activeStatus = config('dashboard.statuses.active', 'амал қилувчи');
        $completedStatus = config('dashboard.statuses.completed', 'Якунланган');

        // Get all contracts with core statuses
        $allContracts = Contract::whereIn('status', [$activeStatus, $completedStatus])->get();

        // Active contracts
        $activeContracts = $allContracts->where('status', $activeStatus);

        // Completed contracts
        $completedContracts = $allContracts->where('status', $completedStatus);

        // Cancelled contracts (other statuses)
        $cancelledContracts = Contract::whereNotIn('status', [$activeStatus, $completedStatus])->get();

        return [
            'contracts_count' => $allContracts->count(),
            'contracts_sum' => $allContracts->sum('contract_amount'),
            'active_count' => $activeContracts->count(),
            'active_sum' => $activeContracts->sum('contract_amount'),
            'cancel_count' => $cancelledContracts->count(),
            'cancel_sum' => $cancelledContracts->sum('contract_amount'),
            'closed_count' => $completedContracts->count(),
            'closed_sum' => $completedContracts->sum('contract_amount'),
        ];
    }

    private function calculateRegionMetrics(): array
    {
        $activeStatus = config('dashboard.statuses.active', 'амал қилувчи');
        $completedStatus = config('dashboard.statuses.completed', 'Якунланған');

        // Get all contracts grouped by district
        $contractsByDistrict = Contract::whereIn('status', [$activeStatus, $completedStatus])
            ->get()
            ->groupBy('district');

        $regions = [];

        foreach ($contractsByDistrict as $district => $contracts) {
            if (empty($district)) continue;

            $activeContracts = $contracts->where('status', $activeStatus);
            $completedContracts = $contracts->where('status', $completedStatus);
            $cancelledContracts = Contract::where('district', $district)
                ->whereNotIn('status', [$activeStatus, $completedStatus])
                ->get();

            // Get contract IDs for this district
            $contractIds = $contracts->pluck('id');

            // Calculate Q3 2025 metrics (months 7, 8, 9)
            $q3_2025 = $this->getQuarterMetrics($contractIds, 2025, 3);

            // Calculate year metrics
            $y2025 = $this->getYearMetrics($contractIds, 2025);
            $y2026 = $this->getYearMetrics($contractIds, 2026);
            $y2027 = $this->getYearMetrics($contractIds, 2027);

            $regions[$district] = [
                'contracts_count' => $contracts->count(),
                'contracts_sum' => $contracts->sum('contract_amount'),
                'active_count' => $activeContracts->count(),
                'active_sum' => $activeContracts->sum('contract_amount'),
                'cancel_count' => $cancelledContracts->count(),
                'cancel_sum' => $cancelledContracts->sum('contract_amount'),
                'closed_count' => $completedContracts->count(),
                'closed_sum' => $completedContracts->sum('contract_amount'),

                // Q3 2025
                'q3_2025_plan' => $q3_2025['plan'],
                'q3_2025_fact' => $q3_2025['fact'],
                'q3_2025_debt' => $q3_2025['debt'],

                // Years
                'y2025_plan' => $y2025['plan'],
                'y2025_fact' => $y2025['fact'],
                'y2025_debt' => $y2025['debt'],

                'y2026_plan' => $y2026['plan'],
                'y2026_fact' => $y2026['fact'],
                'y2026_debt' => $y2026['debt'],

                'y2027_plan' => $y2027['plan'],
                'y2027_fact' => $y2027['fact'],
                'y2027_debt' => $y2027['debt'],
            ];
        }

        // Sort by total amount descending
        uasort($regions, fn($a, $b) => $b['contracts_sum'] <=> $a['contracts_sum']);

        return $regions;
    }

    private function getQuarterMetrics($contractIds, int $year, int $quarter): array
    {
        // Convert quarter to months
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $quarter * 3;

        $schedules = PaymentSchedule::whereIn('contract_id', $contractIds)
            ->where('year', $year)
            ->whereBetween('month', [$startMonth, $endMonth])
            ->get();

        return [
            'plan' => $schedules->sum('planned_amount'),
            'fact' => $schedules->sum('actual_amount'),
            'debt' => $schedules->sum('debt_amount'),
        ];
    }

    private function getYearMetrics($contractIds, int $year): array
    {
        $schedules = PaymentSchedule::whereIn('contract_id', $contractIds)
            ->where('year', $year)
            ->get();

        return [
            'plan' => $schedules->sum('planned_amount'),
            'fact' => $schedules->sum('actual_amount'),
            'debt' => $schedules->sum('debt_amount'),
        ];
    }
}
