<?php

namespace App\Repositories;

use App\Models\Contract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContractRepository
{
    public function getAll(): Collection
    {
        return Contract::all();
    }

    public function getActive(): Collection
    {
        return Contract::active()->get();
    }

    public function getCancelled(): Collection
    {
        return Contract::cancelled()->get();
    }

    public function getCompleted(): Collection
    {
        return Contract::completed()->get();
    }

    public function getTotalCount(): int
    {
        return Contract::count();
    }

    public function getActiveCount(): int
    {
        return Contract::active()->count();
    }

    public function getCancelledCount(): int
    {
        return Contract::cancelled()->count();
    }

    public function getCompletedCount(): int
    {
        return Contract::completed()->count();
    }

    public function getTotalAmount(): float
    {
        return (float) Contract::sum('contract_amount');
    }

    public function getActiveAmount(): float
    {
        return (float) Contract::active()->sum('contract_amount');
    }

    public function getCancelledAmount(): float
    {
        return (float) Contract::cancelled()->sum('contract_amount');
    }

    public function getCompletedAmount(): float
    {
        return (float) Contract::completed()->sum('contract_amount');
    }

    public function getByDistrict(): Collection
    {
        $activeStatus = config('dashboard.statuses.active');
        $cancelledStatus = config('dashboard.statuses.cancelled');
        $completedStatus = config('dashboard.statuses.completed');

        return Contract::select('district')
            ->selectRaw('COUNT(*) as contracts_count')
            ->selectRaw('SUM(contract_amount) as total_amount')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active_count', [$activeStatus])
            ->selectRaw('SUM(CASE WHEN status = ? THEN contract_amount ELSE 0 END) as active_amount', [$activeStatus])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled_count', [$cancelledStatus])
            ->selectRaw('SUM(CASE WHEN status = ? THEN contract_amount ELSE 0 END) as cancelled_amount', [$cancelledStatus])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_count', [$completedStatus])
            ->selectRaw('SUM(CASE WHEN status = ? THEN contract_amount ELSE 0 END) as completed_amount', [$completedStatus])
            ->groupBy('district')
            ->orderBy('total_amount', 'desc')
            ->get();
    }

    public function getDistinctDistricts(): Collection
    {
        return Contract::select('district')
            ->distinct()
            ->orderBy('district')
            ->pluck('district');
    }

    public function getByStatus(string $status): Collection
    {
        return Contract::where('status', $status)->get();
    }

    public function getContractsByDistrict(string $district): Collection
    {
        return Contract::where('district', $district)->get();
    }

    public function getContractIdsByDistrict(string $district): Collection
    {
        return Contract::where('district', $district)->pluck('id');
    }

    public function getLegalEntitiesCount(): int
    {
        return Contract::whereNotNull('inn')->count();
    }

    public function getIndividualsCount(): int
    {
        return Contract::whereNull('inn')
            ->where(function ($query) {
                $query->whereNotNull('pinfl')
                    ->orWhereNotNull('passport');
            })
            ->count();
    }

    public function getWithOverdueSchedules(): Collection
    {
        return Contract::whereHas('schedules', function ($query) {
            $query->where('is_overdue', true);
        })
        ->with(['schedules' => function ($query) {
            $query->where('is_overdue', true);
        }])
        ->get();
    }

    public function getNonCancelledTotalAmount(): float
    {
        return (float) Contract::where('status', '!=', config('dashboard.statuses.cancelled'))
            ->sum('contract_amount');
    }
}
