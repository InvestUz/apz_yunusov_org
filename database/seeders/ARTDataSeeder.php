<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Services\ContractService;
use Carbon\Carbon;

class ARTDataSeeder extends Seeder
{
    protected $contractService;

    public function __construct()
    {
        $this->contractService = new ContractService();
    }

    public function run(): void
    {
        $this->command->info('Starting ART Data Seeding...');

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear existing data
        PaymentSchedule::truncate();
        Payment::truncate();
        Contract::truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Seed Contracts
        $this->seedContracts();

        // Seed Payments
        $this->seedPayments();

        // Match Payments to Contracts
        $this->command->info('Matching payments to contracts...');
        $this->contractService->matchPaymentsToContracts();

        // Generate Payment Schedules
        $this->command->info('Generating payment schedules...');
        $this->generateSchedules();

        // Calculate Debts and Overdue
        $this->command->info('Calculating debts and overdue amounts...');
        $this->contractService->calculateDebtAndOverdue();

        $this->command->info('ART Data Seeding completed successfully!');
    }

    private function seedContracts()
    {
        $this->command->info('Seeding contracts from CSV...');

        $csvPath = public_path('dataset/contracts.csv');
        $contracts = $this->contractService->parseContractCSV($csvPath);

        foreach ($contracts as $contractData) {
            // Extract quarterly payments before creating contract
            $quarterlyPayments = $contractData['quarterly_payments'] ?? [];
            unset($contractData['quarterly_payments']);

            $contract = Contract::create($contractData);

            // Generate payment schedules immediately with quarterly data
            $this->generateSchedulesForContract($contract, $quarterlyPayments);
        }

        $this->command->info('Contracts seeded: ' . count($contracts));
    }

    private function seedPayments()
    {
        $this->command->info('Seeding payments from CSV...');

        $csvPath = public_path('dataset/fakt.csv');
        $payments = $this->contractService->parsePaymentCSV($csvPath);

        foreach ($payments as $paymentData) {
            Payment::create($paymentData);
        }

        $this->command->info('Payments seeded: ' . count($payments));
    }

    private function generateSchedules()
    {
        // Schedules are now generated during contract seeding
        $this->command->info('Payment schedules generated during contract seeding');
    }

    private function generateSchedulesForContract(Contract $contract, array $quarterlyPayments)
    {
        // Map quarters: I Q1 2024, II Q2 2024, III Q3 2024, IV Q4 2024, etc.
        // Array indices 0-15 for 2024-2027 (4 quarters x 4 years)
        $years = [2024, 2025, 2026, 2027];
        $index = 0;

        foreach ($years as $year) {
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $amount = $quarterlyPayments[$index] ?? 0;
                $index++;

                // Skip if amount is zero or null
                if ($amount <= 0) continue;

                // Calculate due date (last day of quarter)
                $month = $quarter * 3;
                $dueDate = Carbon::create($year, $month, 1)->endOfMonth();

                PaymentSchedule::create([
                    'contract_id' => $contract->id,
                    'year' => $year,
                    'quarter' => $quarter,
                    'period' => "{$year} Q{$quarter}",
                    'planned_amount' => $amount,
                    'actual_amount' => 0,
                    'debt_amount' => $amount,
                    'due_date' => $dueDate,
                    'is_overdue' => false,
                ]);
            }
        }
    }
}
