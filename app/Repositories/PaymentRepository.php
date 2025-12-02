<?php

namespace App\Repositories;

use App\Models\Payment;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class PaymentRepository
{
    public function getTotalPaid(): float
    {
        return (float) Payment::sum('amount_debit');
    }

    public function getTotalPaidByPeriod(string $period): float
    {
        $query = Payment::query();

        return (float) $this->applyPeriodFilter($query, $period)->sum('amount_debit');
    }

    public function getNonCancelledTotalPaid(): float
    {
        return (float) Payment::whereHas('contract', function ($q) {
            $q->where('status', '!=', config('dashboard.statuses.cancelled'));
        })->sum('amount_debit');
    }

    public function getByContract(int $contractId): Collection
    {
        return Payment::where('contract_id', $contractId)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    public function getByYear(int $year): Collection
    {
        return Payment::where('year', $year)->get();
    }

    public function getByYearAndMonth(int $year, string $month): Collection
    {
        return Payment::where('year', $year)
            ->where('month', $month)
            ->get();
    }

    public function getRecentPayments(int $limit = 5): Collection
    {
        return Payment::with('contract')
            ->orderBy('payment_date', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getPaymentsByDistrict(string $district): Collection
    {
        return Payment::whereHas('contract', function ($query) use ($district) {
            $query->where('district', $district);
        })->get();
    }

    public function getPaymentsByDistrictAndPeriod(string $district, string $period): float
    {
        $query = Payment::whereHas('contract', function ($q) use ($district) {
            $q->where('district', $district);
        });

        return (float) $this->applyPeriodFilter($query, $period)->sum('amount_debit');
    }

    public function getChartDataByPeriod(string $period): Collection
    {
        $query = Payment::selectRaw('DATE_FORMAT(payment_date, "%Y-%m") as period')
            ->selectRaw('SUM(amount_debit) as actual')
            ->groupBy('period')
            ->orderBy('period');

        if ($period === 'quarter') {
            $query = Payment::selectRaw('YEAR(payment_date) as year')
                ->selectRaw('QUARTER(payment_date) as quarter')
                ->selectRaw('SUM(amount_debit) as actual')
                ->groupBy('year', 'quarter')
                ->orderBy('year')
                ->orderBy('quarter');
        } elseif ($period === 'year') {
            $query = Payment::selectRaw('YEAR(payment_date) as year')
                ->selectRaw('SUM(amount_debit) as actual')
                ->groupBy('year')
                ->orderBy('year');
        }

        return $query->get();
    }

    private function applyPeriodFilter($query, string $period)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'today':
                return $query->whereDate('payment_date', $now->toDateString());

            case 'week':
                return $query->whereBetween('payment_date', [
                    $now->startOfWeek()->toDateString(),
                    $now->endOfWeek()->toDateString()
                ]);

            case 'month':
                return $query->whereYear('payment_date', $now->year)
                    ->whereMonth('payment_date', $now->month);

            case 'quarter':
                $quarter = $now->quarter;
                return $query->whereYear('payment_date', $now->year)
                    ->whereRaw('QUARTER(payment_date) = ?', [$quarter]);

            default:
                return $query;
        }
    }
}
