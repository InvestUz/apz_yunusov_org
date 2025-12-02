<?php

namespace App\DTOs;

class DistrictStatsDTO
{
    public function __construct(
        public readonly string $districtName,
        public readonly int $contractsCount,
        public readonly float $totalAmount,
        public readonly int $activeCount,
        public readonly float $activeAmount,
        public readonly int $cancelledCount,
        public readonly float $cancelledAmount,
        public readonly int $completedCount,
        public readonly float $completedAmount,
        public readonly float $paidToday,
        public readonly float $paidWeek,
        public readonly float $paidMonth,
        public readonly float $paidQuarter,
        public readonly float $paidAmount,
        // Q3 2025
        public readonly float $q3_2025_plan = 0,
        public readonly float $q3_2025_fact = 0,
        public readonly float $q3_2025_debt = 0,
        // Q4 2025
        public readonly float $q4_2025_plan = 0,
        public readonly float $q4_2025_fact = 0,
        public readonly float $q4_2025_debt = 0,
        // Year 2026
        public readonly float $year_2026_plan = 0,
        public readonly float $year_2026_fact = 0,
        public readonly float $year_2026_debt = 0,
        // Year 2027
        public readonly float $year_2027_plan = 0,
        public readonly float $year_2027_fact = 0,
        public readonly float $year_2027_debt = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'district_name' => $this->districtName,
            'contracts_count' => $this->contractsCount,
            'total_amount' => $this->totalAmount,
            'active_count' => $this->activeCount,
            'active_amount' => $this->activeAmount,
            'cancelled_count' => $this->cancelledCount,
            'cancelled_amount' => $this->cancelledAmount,
            'completed_count' => $this->completedCount,
            'completed_amount' => $this->completedAmount,
            'paid_today' => $this->paidToday,
            'paid_week' => $this->paidWeek,
            'paid_month' => $this->paidMonth,
            'paid_quarter' => $this->paidQuarter,
            'paid_amount' => $this->paidAmount,
        ];
    }
}
