<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $period = $request->get('period_filter', 'all');

        $stats = $this->dashboardService->getDashboardStats($period);
        $districtStats = $this->dashboardService->getDistrictStats();
        $chartData = $this->dashboardService->getChartData('month');
        $statusDistribution = array_values(array_filter(
            $this->dashboardService->getStatusDistribution(),
            fn($s) => in_array($s['code'], [config('dashboard.statuses.active'), config('dashboard.statuses.completed')])
        ));
        $recentContracts = $this->dashboardService->getRecentContracts();
        $recentPayments = $this->dashboardService->getRecentPayments();

        if ($request->wantsJson()) {
            return response()->json([
                'stats' => $stats->toArray(),
                'districtStats' => $districtStats->map->toArray(),
            ]);
        }

        return view('dashboard.index', compact(
            'stats',
            'districtStats',
            'chartData',
            'statusDistribution',
            'recentContracts',
            'recentPayments'
        ));
    }

    public function chartData(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');
        $data = $this->dashboardService->getChartData($period);

        return response()->json($data);
    }

    public function summary(): JsonResponse
    {
        $stats = $this->dashboardService->getDashboardStats();
        return response()->json($stats->toArray());
    }

    public function contracts(): JsonResponse
    {
        $districtStats = $this->dashboardService->getDistrictStats();
        return response()->json($districtStats->map->toArray());
    }

    public function debts(): JsonResponse
    {
        $schedules = $this->dashboardService->getScheduleByPeriod();
        return response()->json($schedules);
    }

    public function overdue(): JsonResponse
    {
        $overdueContracts = $this->dashboardService->getOverdueContracts();
        return response()->json($overdueContracts);
    }
}
