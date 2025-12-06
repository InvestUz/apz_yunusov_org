<?php

namespace App\DTOs;

class DashboardStatsDTO
{
    public function __construct(
        public readonly int $totalContracts,
        public readonly float $totalAmount,
        public readonly int $activeContracts,
        public readonly float $activeAmount,
        public readonly int $cancelledContracts,
        public readonly float $cancelledAmount,
        public readonly int $completedContracts,
        public readonly float $completedAmount,
        public readonly int $legalEntities,
        public readonly int $individuals,
        public readonly float $totalPaid,
        public readonly float $totalDebt,
        public readonly int $paidContractsCount,
        public readonly int $debtorsCount,
        public readonly float $todayDebt = 0.0
    ) {}

    public function toArray(): array
    {
        return [
            'total_contracts' => $this->totalContracts,
            'total_amount' => $this->totalAmount,
            'active_contracts' => $this->activeContracts,
            'active_amount' => $this->activeAmount,
            'cancelled_contracts' => $this->cancelledContracts,
            'cancelled_amount' => $this->cancelledAmount,
            'completed_contracts' => $this->completedContracts,
            'completed_amount' => $this->completedAmount,
            'legal_entities' => $this->legalEntities,
            'individuals' => $this->individuals,
            'total_paid' => $this->totalPaid,
            'total_debt' => $this->totalDebt,
            'paid_contracts_count' => $this->paidContractsCount,
            'debtors_count' => $this->debtorsCount,
            'today_debt' => $this->todayDebt,
        ];
    }
}
