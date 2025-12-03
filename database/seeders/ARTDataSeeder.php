<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Services\ContractService;
use Carbon\Carbon;
use Exception;

class ARTDataSeeder extends Seeder
{
    protected $contractService;
    protected $errors = [];
    protected $warnings = [];

    public function __construct()
    {
        $this->contractService = new ContractService();
    }

    public function run(): void
    {
        $this->command->info('===========================================');
        $this->command->info('Starting ART Production Data Seeding...');
        $this->command->info('===========================================');

        $startTime = microtime(true);

        try {
            // Verify CSV file exists before proceeding
            $this->verifyCsvFiles();

            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Clear existing data
            $this->command->warn('Clearing existing data...');
            PaymentSchedule::truncate();
            Payment::truncate();
            Contract::truncate();
            $this->command->info('✓ Existing data cleared');

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Seed Contracts
            $contractCount = $this->seedContracts();

            // Seed Payments (only if payment CSV exists)
            $paymentCount = 0;
            if (file_exists(public_path('dataset/fakt.csv'))) {
                $paymentCount = $this->seedPayments();
            } else {
                $this->command->warn('Payment file (fakt.csv) not found - skipping payment seeding');
            }

            // Match Payments to Contracts
            if ($paymentCount > 0) {
                $this->command->info('Matching payments to contracts...');
                $this->contractService->matchPaymentsToContracts();
                $this->command->info('✓ Payments matched to contracts');
            }

            // Generate Payment Schedules
            $this->command->info('Payment schedules already generated during contract seeding');

            // Calculate Debts and Overdue
            $this->command->info('Calculating debts and overdue amounts...');
            $this->contractService->calculateDebtAndOverdue();
            $this->command->info('✓ Debts and overdue amounts calculated');

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            // Display summary
            $this->displaySummary($contractCount, $paymentCount, $duration);

        } catch (Exception $e) {
            $this->command->error('===========================================');
            $this->command->error('SEEDING FAILED!');
            $this->command->error('===========================================');
            $this->command->error('Error: ' . $e->getMessage());
            $this->command->error('File: ' . $e->getFile());
            $this->command->error('Line: ' . $e->getLine());
            Log::error('ART Data Seeding Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function verifyCsvFiles(): void
    {
        $contractCsv = public_path('dataset/contracts.csv');

        if (!file_exists($contractCsv)) {
            throw new Exception("Contract CSV file not found at: {$contractCsv}");
        }

        if (!is_readable($contractCsv)) {
            throw new Exception("Contract CSV file is not readable: {$contractCsv}");
        }

        $this->command->info('✓ CSV files verified');
    }

    private function seedContracts(): int
    {
        $this->command->info('Seeding contracts from CSV...');

        $csvPath = public_path('dataset/contracts.csv');

        try {
            $contracts = $this->contractService->parseContractCSV($csvPath);
            $totalContracts = count($contracts);
            $successCount = 0;
            $errorCount = 0;

            $this->command->info("Found {$totalContracts} contracts to seed");

            $progressBar = $this->command->getOutput()->createProgressBar($totalContracts);
            $progressBar->start();

            foreach ($contracts as $index => $contractData) {
                try {
                    // Validate contract data
                    if (empty($contractData['contract_number'])) {
                        $this->warnings[] = "Row " . ($index + 2) . ": Missing contract number - skipped";
                        $errorCount++;
                        $progressBar->advance();
                        continue;
                    }

                    // Check for duplicate contract number
                    $existingContract = Contract::where('contract_number', $contractData['contract_number'])->first();
                    if ($existingContract) {
                        $this->warnings[] = "Contract {$contractData['contract_number']} already exists - skipped";
                        $errorCount++;
                        $progressBar->advance();
                        continue;
                    }

                    // Extract quarterly payments before creating contract
                    $quarterlyPayments = $contractData['quarterly_payments'] ?? [];
                    unset($contractData['quarterly_payments']);

                    // Create contract
                    $contract = Contract::create($contractData);

                    // Generate payment schedules immediately with quarterly data
                    $this->generateSchedulesForContract($contract, $quarterlyPayments);

                    $successCount++;

                } catch (Exception $e) {
                    $errorCount++;
                    $contractNum = $contractData['contract_number'] ?? 'Unknown';
                    $this->errors[] = "Contract {$contractNum}: " . $e->getMessage();
                    Log::error("Failed to seed contract {$contractNum}", ['error' => $e->getMessage()]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->command->newLine(2);

            $this->command->info("✓ Contracts seeded: {$successCount} successful, {$errorCount} failed");

            if ($errorCount > 0) {
                $this->command->warn("⚠ {$errorCount} contracts failed to import");
            }

            return $successCount;

        } catch (Exception $e) {
            $this->command->error('Failed to parse contract CSV: ' . $e->getMessage());
            throw $e;
        }
    }

    private function seedPayments(): int
    {
        $this->command->info('Seeding payments from CSV...');

        $csvPath = public_path('dataset/fakt.csv');

        try {
            $payments = $this->contractService->parsePaymentCSV($csvPath);
            $totalPayments = count($payments);
            $successCount = 0;
            $errorCount = 0;

            $this->command->info("Found {$totalPayments} payments to seed");

            $progressBar = $this->command->getOutput()->createProgressBar($totalPayments);
            $progressBar->start();

            foreach ($payments as $index => $paymentData) {
                try {
                    // Validate payment data
                    if (empty($paymentData['payment_date'])) {
                        $this->warnings[] = "Payment row " . ($index + 2) . ": Missing payment date - skipped";
                        $errorCount++;
                        $progressBar->advance();
                        continue;
                    }

                    if (($paymentData['amount_debit'] ?? 0) <= 0 && ($paymentData['amount_credit'] ?? 0) <= 0) {
                        $this->warnings[] = "Payment row " . ($index + 2) . ": Zero amount - skipped";
                        $errorCount++;
                        $progressBar->advance();
                        continue;
                    }

                    Payment::create($paymentData);
                    $successCount++;

                } catch (Exception $e) {
                    $errorCount++;
                    $this->errors[] = "Payment row " . ($index + 2) . ": " . $e->getMessage();
                    Log::error("Failed to seed payment at row " . ($index + 2), ['error' => $e->getMessage()]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->command->newLine(2);

            $this->command->info("✓ Payments seeded: {$successCount} successful, {$errorCount} failed");

            if ($errorCount > 0) {
                $this->command->warn("⚠ {$errorCount} payments failed to import");
            }

            return $successCount;

        } catch (Exception $e) {
            $this->command->error('Failed to parse payment CSV: ' . $e->getMessage());
            throw $e;
        }
    }

    private function displaySummary(int $contractCount, int $paymentCount, float $duration): void
    {
        $this->command->newLine();
        $this->command->info('===========================================');
        $this->command->info('SEEDING COMPLETED SUCCESSFULLY!');
        $this->command->info('===========================================');
        $this->command->info("Contracts imported: {$contractCount}");
        $this->command->info("Payments imported: {$paymentCount}");
        $this->command->info("Duration: {$duration} seconds");

        $scheduleCount = PaymentSchedule::count();
        $this->command->info("Payment schedules created: {$scheduleCount}");

        $matchedPayments = Payment::where('is_matched', true)->count();
        if ($paymentCount > 0) {
            $matchRate = round(($matchedPayments / $paymentCount) * 100, 2);
            $this->command->info("Payments matched: {$matchedPayments}/{$paymentCount} ({$matchRate}%)");
        }

        if (count($this->warnings) > 0) {
            $this->command->newLine();
            $this->command->warn('Warnings (' . count($this->warnings) . '):');
            foreach (array_slice($this->warnings, 0, 10) as $warning) {
                $this->command->warn('  • ' . $warning);
            }
            if (count($this->warnings) > 10) {
                $this->command->warn('  ... and ' . (count($this->warnings) - 10) . ' more warnings');
            }
        }

        if (count($this->errors) > 0) {
            $this->command->newLine();
            $this->command->error('Errors (' . count($this->errors) . '):');
            foreach (array_slice($this->errors, 0, 10) as $error) {
                $this->command->error('  • ' . $error);
            }
            if (count($this->errors) > 10) {
                $this->command->error('  ... and ' . (count($this->errors) - 10) . ' more errors');
            }
        }

        $this->command->info('===========================================');
    }

    private function generateSchedulesForContract(Contract $contract, array $quarterlyPayments): void
    {
        try {
            // Check if this is a Muddatsiz (one-time payment) contract
            // Muddatsiz contracts have no quarterly payments (all zeros)
            $hasQuarterlyPayments = array_sum($quarterlyPayments) > 0;

            if (!$hasQuarterlyPayments) {
                // Muddatsiz: One-time payment contract
                // Create a single schedule entry for the initial/remaining payment
                $plannedAmount = $contract->remaining_amount > 0 ? $contract->remaining_amount : $contract->initial_payment;

                if ($plannedAmount > 0) {
                    PaymentSchedule::create([
                        'contract_id' => $contract->id,
                        'year' => $contract->completion_date ? $contract->completion_date->year : Carbon::now()->year,
                        'quarter' => $contract->completion_date ? (int)ceil($contract->completion_date->month / 3) : 1,
                        'period' => 'Muddatsiz', // One-time payment
                        'planned_amount' => $plannedAmount,
                        'actual_amount' => 0,
                        'debt_amount' => $plannedAmount,
                        'due_date' => $contract->completion_date ?? Carbon::now(),
                        'is_overdue' => false,
                    ]);
                }
            } else {
                // Muddatli: Scheduled payment contract
                // Map quarters: I Q1 2024, II Q2 2024, III Q3 2024, IV Q4 2024, etc.
                // Array indices 0-15 for 2024-2027 (4 quarters x 4 years)
                // Extended to support up to 2030 based on CSV structure
                $years = [2024, 2025, 2026, 2027, 2028, 2029, 2030];
                $index = 0;

                foreach ($years as $year) {
                    for ($quarter = 1; $quarter <= 4; $quarter++) {
                        // Check if we have data for this index
                        if ($index >= count($quarterlyPayments)) {
                            break 2; // Exit both loops
                        }

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
        } catch (Exception $e) {
            $this->errors[] = "Failed to generate schedules for contract {$contract->contract_number}: " . $e->getMessage();
            Log::error("Schedule generation failed for contract {$contract->contract_number}", [
                'error' => $e->getMessage(),
                'contract_id' => $contract->id
            ]);
            throw $e;
        }
    }
}
