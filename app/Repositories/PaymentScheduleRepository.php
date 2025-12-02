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
            ->orderBy('quarter')
            ->get();
    }

    public function getGroupedByPeriod(): Collection
    {
        return PaymentSchedule::select('year', 'quarter', 'period')
            ->selectRaw('SUM(planned_amount) as planned')
            ->selectRaw('SUM(actual_amount) as actual')
            ->selectRaw('SUM(debt_amount) as debt')
            ->groupBy('year', 'quarter', 'period')
            ->orderBy('year')
            ->orderBy('quarter')
            ->get();
    }

    public function getByDistrictContracts(Collection $contractIds): Collection
    {
        return PaymentSchedule::whereIn('contract_id', $contractIds)
            ->select('year', 'quarter', 'period')
            ->selectRaw('SUM(planned_amount) as planned')
            ->selectRaw('SUM(actual_amount) as actual')
            ->selectRaw('SUM(debt_amount) as debt')
            ->groupBy('year', 'quarter', 'period')
            ->orderBy('year')
            ->orderBy('quarter')
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
        return PaymentSchedule::where('year', $year)
            ->where('quarter', $quarter)
            ->get();
    }

    public function getPlannedForPeriod(string $period): float
    {
        return (float) PaymentSchedule::where('period', $period)
            ->sum('planned_amount');
    }

    public function getActualForPeriod(string $period): float
    {
        return (float) PaymentSchedule::where('period', $period)
            ->sum('actual_amount');
    }

    public function getByDistrictAndQuarter(Collection $contractIds, int $year, int $quarter): array
    {
        $schedule = PaymentSchedule::whereIn('contract_id', $contractIds)
            ->where('year', $year)
            ->where('quarter', $quarter)
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
