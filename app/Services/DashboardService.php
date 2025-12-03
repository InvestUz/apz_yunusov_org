<?php

namespace App\Services;

use App\DTOs\DashboardStatsDTO;
use App\DTOs\DistrictStatsDTO;
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
        $totalPaid = $period
            ? $this->paymentRepository->getTotalPaidByPeriod($period)
            : $this->paymentRepository->getTotalPaid();

        $totalDebt = $this->calculateTotalDebt();

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
            debtorsCount: $this->getDebtorsCount()
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
                paidToday: $this->paymentRepository->getPaymentsByDistrictAndPeriod($district->district, 'today'),
                paidWeek: $this->paymentRepository->getPaymentsByDistrictAndPeriod($district->district, 'week'),
                paidMonth: $this->paymentRepository->getPaymentsByDistrictAndPeriod($district->district, 'month'),
                paidQuarter: $this->paymentRepository->getPaymentsByDistrictAndPeriod($district->district, 'quarter'),
                paidAmount: $this->paymentRepository->getPaymentsByDistrictAndPeriod($district->district, 'all'),
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
        return $this->groupSchedulesByYear($schedules);
    }

    private function groupSchedulesByYear($schedules)
    {
        $grouped = [];

        foreach ($schedules as $schedule) {
            $year = $schedule->year;
            $quarter = $schedule->quarter;

            if (!isset($grouped[$year])) {
                $grouped[$year] = [
                    'year' => $year,
                    'quarters' => [],
                    'total_planned' => 0,
                    'total_actual' => 0,
                    'total_debt' => 0,
                ];
            }

            $grouped[$year]['quarters'][$quarter] = [
                'quarter' => $quarter,
                'period' => $schedule->period,
                'planned' => (float)$schedule->planned,
                'actual' => (float)$schedule->actual,
                'debt' => (float)$schedule->debt,
            ];

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
        return $this->paymentRepository->getChartDataByPeriod($period);
    }

    public function getRecentContracts(int $limit = 5): Collection
    {
        return $this->contractRepository->getAll()
            ->sortByDesc('created_at')
            ->take($limit);
    }

    public function getRecentPayments(int $limit = 5): Collection
    {        return $this->paymentRepository->getRecentPayments($limit);
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
        $totalAmount = $this->contractRepository->getTotalAmountForCoreStatuses();
        $totalPaid = $this->paymentRepository->getNonCancelledTotalPaid();

        return max(0, $totalAmount - $totalPaid);
    }

    private function calculateOverdueDebt(): float
    {
        return $this->scheduleRepository->getTotalOverdueDebt();
    }

    private function getPaidContractsCount(): int
    {
        return $this->contractRepository->getAll()
            ->filter(fn($contract) => $contract->total_paid > 0)
            ->count();
    }

    private function getDebtorsCount(): int
    {
        return $this->contractRepository->getActive()
            ->filter(fn($contract) => $contract->total_debt > 0)
            ->count();
    }

    private function getDistrictSchedules(string $district): array
    {
        $contractIds = $this->contractRepository->getContractIdsByDistrict($district);
        $schedules = $this->scheduleRepository->getByDistrictContracts($contractIds);

        $periods = [];
        foreach ($schedules as $schedule) {
            $periods[$schedule->period] = [
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
