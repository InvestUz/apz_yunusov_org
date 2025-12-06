<?php

namespace App\Repositories;

use App\Models\PaymentSchedule;
use Illuminate\Support\Collection;

class PaymentScheduleRepository
{
    public function getByContract(int $contractId): Collection
    {
        return PaymentSchedule::where('contract_id', $contractId)
            ->orderBy('year')
            ->orderBy('month')
            ->get();
    }

    public function getGroupedByPeriod(): Collection
    {
        return PaymentSchedule::select('year', 'month', 'period_label')
            ->selectRaw('SUM(planned_amount) as planned')
            ->selectRaw('SUM(actual_amount) as actual')
            ->selectRaw('SUM(debt_amount) as debt')
            ->groupBy('year', 'month', 'period_label')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
    }

    public function getByDistrictContracts(Collection $contractIds): Collection
    {
        return PaymentSchedule::whereIn('contract_id', $contractIds)
            ->select('year', 'month', 'period_label')
            ->selectRaw('SUM(planned_amount) as planned')
            ->selectRaw('SUM(actual_amount) as actual')
            ->selectRaw('SUM(debt_amount) as debt')
            ->groupBy('year', 'month', 'period_label')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
    }

    public function getTotalOverdueDebt(): float
    {
        return (float) PaymentSchedule::where('is_overdue', true)
            ->sum('debt_amount');
    }

    public function getOverdueSchedules(): Collection
    {
        return PaymentSchedule::where('is_overdue', true)
            ->with('contract')
            ->get();
    }

    public function getByYearAndQuarter(int $year, int $quarter): Collection
    {
        // Convert quarter to months
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $quarter * 3;

        return PaymentSchedule::where('year', $year)
            ->whereBetween('month', [$startMonth, $endMonth])
            ->get();
    }

    public function getPlannedForPeriod(string $period): float
    {
        return (float) PaymentSchedule::where('period_label', $period)
            ->sum('planned_amount');
    }

    public function getActualForPeriod(string $period): float
    {
        return (float) PaymentSchedule::where('period_label', $period)
            ->sum('actual_amount');
    }

    public function getByDistrictAndQuarter(Collection $contractIds, int $year, int $quarter): array
    {
        // Convert quarter to months
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $quarter * 3;

        $schedule = PaymentSchedule::whereIn('contract_id', $contractIds)
            ->where('year', $year)
            ->whereBetween('month', [$startMonth, $endMonth])
            ->selectRaw('SUM(planned_amount) as planned')
            ->selectRaw('SUM(actual_amount) as actual')
            ->selectRaw('SUM(debt_amount) as debt')
            ->first();

        return [
            'planned' => $schedule->planned ?? 0,
            'actual' => $schedule->actual ?? 0,
            'debt' => $schedule->debt ?? 0,
        ];
    }

    public function getByDistrictAndYear(Collection $contractIds, int $year): array
    {
        $schedule = PaymentSchedule::whereIn('contract_id', $contractIds)
            ->where('year', $year)
            ->selectRaw('SUM(planned_amount) as planned')
            ->selectRaw('SUM(actual_amount) as actual')
            ->selectRaw('SUM(debt_amount) as debt')
            ->first();

        return [
            'planned' => $schedule->planned ?? 0,
            'actual' => $schedule->actual ?? 0,
            'debt' => $schedule->debt ?? 0,
        ];
    }
}
