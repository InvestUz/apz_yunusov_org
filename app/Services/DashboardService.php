<?php

namespace App\Services;

use App\DTOs\DashboardStatsDTO;
use App\DTOs\DistrictStatsDTO;
use App\Models\PaymentSchedule;
use App\Repositories\ContractRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\PaymentScheduleRepository;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private readonly ContractRepository $contractRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly PaymentScheduleRepository $scheduleRepository
    ) {}

    public function getDashboardStats(?string $period = null): DashboardStatsDTO
    {
        // Use payment schedules for all calculations since payments table is not used
        $totalPaid = $period
            ? $this->getTotalPaidByPeriodFromSchedules($period)
            : $this->getTotalPaidFromSchedules();

        $totalDebt = $this->calculateTotalDebt();
        $todayDebt = $this->calculateTodayDebt();

        return new DashboardStatsDTO(
            totalContracts: $this->contractRepository->getTotalCountForCoreStatuses(),
            totalAmount: $this->contractRepository->getTotalAmountForCoreStatuses(),
            activeContracts: $this->contractRepository->getActiveCount(),
            activeAmount: $this->contractRepository->getActiveAmount(),
            cancelledContracts: 0,
            cancelledAmount: 0,
            completedContracts: $this->contractRepository->getCompletedCount(),
            completedAmount: $this->contractRepository->getCompletedAmount(),
            legalEntities: $this->contractRepository->getLegalEntitiesCount(),
            individuals: $this->contractRepository->getIndividualsCount(),
            totalPaid: $totalPaid,
            totalDebt: $totalDebt,
            paidContractsCount: $this->getPaidContractsCount(),
            debtorsCount: $this->getDebtorsCount(),
            todayDebt: $todayDebt
        );
    }

    public function getDistrictStats(): Collection
    {
        $districts = $this->contractRepository->getByDistrict();

        return $districts->map(function ($district) {
            // Get contract IDs for this district
            $contractIds = $this->contractRepository->getContractIdsByDistrict($district->district);

            // Get schedule data for different periods
            $q3_2025 = $this->scheduleRepository->getByDistrictAndQuarter($contractIds, 2025, 3);
            $q4_2025 = $this->scheduleRepository->getByDistrictAndQuarter($contractIds, 2025, 4);
            $year_2026 = $this->scheduleRepository->getByDistrictAndYear($contractIds, 2026);
            $year_2027 = $this->scheduleRepository->getByDistrictAndYear($contractIds, 2027);

            return new DistrictStatsDTO(
                districtName: $district->district,
                contractsCount: (int) $district->contracts_count,
                totalAmount: (float) $district->total_amount,
                activeCount: (int) $district->active_count,
                activeAmount: (float) $district->active_amount,
                cancelledCount: (int) $district->cancelled_count,
                cancelledAmount: (float) $district->cancelled_amount,
                completedCount: (int) $district->completed_count,
                completedAmount: (float) $district->completed_amount,
                paidToday: $this->getPaymentsByDistrictAndPeriodFromSchedules($district->district, 'today'),
                paidWeek: $this->getPaymentsByDistrictAndPeriodFromSchedules($district->district, 'week'),
                paidMonth: $this->getPaymentsByDistrictAndPeriodFromSchedules($district->district, 'month'),
                paidQuarter: $this->getPaymentsByDistrictAndPeriodFromSchedules($district->district, 'quarter'),
                paidAmount: $this->getPaymentsByDistrictAndPeriodFromSchedules($district->district, 'all'),
                // Q3 2025
                q3_2025_plan: (float) $q3_2025['planned'],
                q3_2025_fact: (float) $q3_2025['actual'],
                q3_2025_debt: (float) $q3_2025['debt'],
                // Q4 2025
                q4_2025_plan: (float) $q4_2025['planned'],
                q4_2025_fact: (float) $q4_2025['actual'],
                q4_2025_debt: (float) $q4_2025['debt'],
                // Year 2026
                year_2026_plan: (float) $year_2026['planned'],
                year_2026_fact: (float) $year_2026['actual'],
                year_2026_debt: (float) $year_2026['debt'],
                // Year 2027
                year_2027_plan: (float) $year_2027['planned'],
                year_2027_fact: (float) $year_2027['actual'],
                year_2027_debt: (float) $year_2027['debt'],
            );
        });
    }

    public function getScheduleByPeriod(): array
    {
        $schedules = $this->scheduleRepository->getGroupedByPeriod();
        return $this->groupSchedulesByYearAndQuarter($schedules);
    }

    private function groupSchedulesByYearAndQuarter($schedules)
    {
        $grouped = [];

        foreach ($schedules as $schedule) {
            $year = $schedule->year;
            $month = $schedule->month;

            // Calculate quarter from month
            $quarter = (int)ceil($month / 3);

            if (!isset($grouped[$year])) {
                $grouped[$year] = [
                    'year' => $year,
                    'quarters' => [],
                    'total_planned' => 0,
                    'total_actual' => 0,
                    'total_debt' => 0,
                ];
            }

            // Initialize quarter if not exists
            if (!isset($grouped[$year]['quarters'][$quarter])) {
                $grouped[$year]['quarters'][$quarter] = [
                    'quarter' => $quarter,
                    'period' => "{$year} Q{$quarter}",
                    'planned' => 0,
                    'actual' => 0,
                    'debt' => 0,
                ];
            }

            // Aggregate monthly data into quarters
            $grouped[$year]['quarters'][$quarter]['planned'] += (float)$schedule->planned;
            $grouped[$year]['quarters'][$quarter]['actual'] += (float)$schedule->actual;
            $grouped[$year]['quarters'][$quarter]['debt'] += (float)$schedule->debt;

            $grouped[$year]['total_planned'] += (float)$schedule->planned;
            $grouped[$year]['total_actual'] += (float)$schedule->actual;
            $grouped[$year]['total_debt'] += (float)$schedule->debt;
        }

        return array_values($grouped);
    }

    public function getOverdueContracts(): Collection
    {
        return $this->contractRepository->getWithOverdueSchedules()
            ->map(function ($contract) {
                $contract->overdue_amount = $contract->schedules->sum('debt_amount');
                return $contract;
            });
    }

    public function getChartData(string $period = 'month'): Collection
    {
        // Use payment schedules instead of payments table (which is empty)
        // since we're working with APZ CSV data that has schedules, not actual payments

        if ($period === 'quarter') {
            return $this->getQuarterlyChartData();
        } elseif ($period === 'year') {
            return $this->getYearlyChartData();
        }

        // Default: monthly chart data (limited to last 12 months)
        return $this->getMonthlyChartData();
    }

    private function getMonthlyChartData(): Collection
    {
        $schedules = PaymentSchedule::selectRaw('year, month')
            ->selectRaw('SUM(actual_amount) as actual')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function($item) {
                $item->label = sprintf('%d-%02d', $item->year, $item->month);
                return $item;
            });

        // Return only last 12 months to prevent chart overflow
        return $schedules->slice(-12)->values();
    }

    private function getQuarterlyChartData(): Collection
    {
        $schedules = PaymentSchedule::selectRaw('year')
            ->selectRaw('CEIL(month / 3) as quarter')
            ->selectRaw('SUM(actual_amount) as actual')
            ->groupBy('year', 'quarter')
            ->orderBy('year')
            ->orderByRaw('CEIL(month / 3)')
            ->get()
            ->map(function($item) {
                $item->label = sprintf('%d Q%d', $item->year, $item->quarter);
                return $item;
            });

        // Return only last 8 quarters (2 years)
        return $schedules->slice(-8)->values();
    }

    private function getYearlyChartData(): Collection
    {
        return PaymentSchedule::selectRaw('year')
            ->selectRaw('SUM(actual_amount) as actual')
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->map(function($item) {
                $item->label = (string)$item->year;
                return $item;
            });
    }

    public function getRecentContracts(int $limit = 5): Collection
    {
        return $this->contractRepository->getAll()
            ->sortByDesc('created_at')
            ->take($limit);
    }

    public function getRecentPayments(int $limit = 5): Collection
    {
        // Get recent payment schedules with actual amounts instead of payments table
        return PaymentSchedule::with('contract')
            ->where('actual_amount', '>', 0)
            ->orderBy('period_date', 'desc')
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($schedule) {
                return (object)[
                    'contract' => $schedule->contract,
                    'amount' => $schedule->actual_amount,
                    'payment_date' => $schedule->period_date,
                    'period_label' => $schedule->period_label,
                ];
            });
    }

    public function getStatusDistribution(): array
    {
        $statuses = config('dashboard.status_config');

        return [
            [
                'name_uz' => $statuses['active']['label_uz'],
                'code' => $statuses['active']['code'],
                'color' => $statuses['active']['color'],
                'count' => $this->contractRepository->getActiveCount(),
            ],
            [
                'name_uz' => $statuses['completed']['label_uz'],
                'code' => $statuses['completed']['code'],
                'color' => $statuses['completed']['color'],
                'count' => $this->contractRepository->getCompletedCount(),
            ],
        ];
    }

    private function calculateTotalDebt(): float
    {
        // Calculate from payment schedules: sum of all debt amounts
        return PaymentSchedule::sum('debt_amount');
    }

    /**
     * Calculate today's debt (текущая задолженность на сегодня)
     * Formula: sum(planned up to today) - sum(actual)
     */
    private function calculateTodayDebt(): float
    {
        $today = now();

        // Get sum of planned amounts for periods up to today
        $plannedUpToToday = PaymentSchedule::whereDate('period_date', '<=', $today)
            ->sum('planned_amount');

        // Get sum of all actual payments
        $totalActual = PaymentSchedule::sum('actual_amount');

        // Today's debt = planned up to today - actual
        return max(0, $plannedUpToToday - $totalActual);
    }

    private function calculateOverdueDebt(): float
    {
        return $this->scheduleRepository->getTotalOverdueDebt();
    }

    private function getPaidContractsCount(): int
    {
        // Count contracts that have actual payments in schedules
        return PaymentSchedule::where('actual_amount', '>', 0)
            ->distinct('contract_id')
            ->count('contract_id');
    }

    private function getDebtorsCount(): int
    {
        // Count contracts with positive debt
        return PaymentSchedule::where('debt_amount', '>', 0)
            ->distinct('contract_id')
            ->count('contract_id');
    }

    /**
     * Get total paid from payment schedules
     */
    private function getTotalPaidFromSchedules(): float
    {
        return PaymentSchedule::sum('actual_amount');
    }

    /**
     * Get total paid by period from payment schedules
     */
    private function getTotalPaidByPeriodFromSchedules(string $period): float
    {
        $query = PaymentSchedule::query();

        switch ($period) {
            case 'today':
                $query->whereDate('period_date', today());
                break;
            case 'week':
                $query->whereBetween('period_date', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereYear('period_date', now()->year)
                      ->whereMonth('period_date', now()->month);
                break;
            case 'quarter':
                $currentQuarter = (int)ceil(now()->month / 3);
                $startMonth = ($currentQuarter - 1) * 3 + 1;
                $endMonth = $currentQuarter * 3;
                $query->whereYear('year', now()->year)
                      ->whereBetween('month', [$startMonth, $endMonth]);
                break;
            case 'year':
                $query->where('year', now()->year);
                break;
        }

        return $query->sum('actual_amount');
    }

    /**
     * Get payments by district and period from schedules
     */
    private function getPaymentsByDistrictAndPeriodFromSchedules(string $district, string $period): float
    {
        $contractIds = $this->contractRepository->getContractIdsByDistrict($district);
        $query = PaymentSchedule::whereIn('contract_id', $contractIds);

        switch ($period) {
            case 'today':
                $query->whereDate('period_date', today());
                break;
            case 'week':
                $query->whereBetween('period_date', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereYear('period_date', now()->year)
                      ->whereMonth('period_date', now()->month);
                break;
            case 'quarter':
                $currentQuarter = (int)ceil(now()->month / 3);
                $startMonth = ($currentQuarter - 1) * 3 + 1;
                $endMonth = $currentQuarter * 3;
                $query->whereYear('year', now()->year)
                      ->whereBetween('month', [$startMonth, $endMonth]);
                break;
            case 'all':
            default:
                // No date filter
                break;
        }

        return $query->sum('actual_amount');
    }

    private function getDistrictSchedules(string $district): array
    {
        $contractIds = $this->contractRepository->getContractIdsByDistrict($district);
        $schedules = $this->scheduleRepository->getByDistrictContracts($contractIds);

        $periods = [];
        foreach ($schedules as $schedule) {
            // Use period_label for monthly data
            $periodKey = $schedule->period_label ?? $schedule->month;
            $periods[$periodKey] = [
                'planned' => (float) $schedule->planned / 1000000000,
                'actual' => (float) $schedule->actual / 1000000000,
                'debt' => (float) $schedule->debt / 1000000000,
            ];
        }

        return $periods;
    }

    public function formatAmount(float $amount): string
    {
        $divisor = config('dashboard.formatting.billion_divisor');
        $suffix = config('dashboard.formatting.billion_suffix');
        $decimals = config('dashboard.formatting.decimal_places');

        return number_format($amount / $divisor, $decimals, '.', ' ') . ' ' . $suffix;
    }
}
