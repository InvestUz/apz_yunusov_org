<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use Carbon\Carbon;
use Exception;

class ARTDataSeeder extends Seeder
{
    protected $errors = [];
    protected $warnings = [];

    public function run(): void
    {
        $this->command->info('===========================================');
        $this->command->info('Starting APZ Data Seeding...');
        $this->command->info('===========================================');

        $startTime = microtime(true);

        try {
            $csvPath = public_path('dataset/APZ chorak bo\'yicha (2).csv');

            if (!file_exists($csvPath)) {
                throw new Exception("CSV file not found at: {$csvPath}");
            }

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

            // Parse and seed data
            $contractCount = $this->seedFromCSV($csvPath);

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            // Display summary
            $this->displaySummary($contractCount, $duration);

        } catch (Exception $e) {
            $this->command->error('===========================================');
            $this->command->error('SEEDING FAILED!');
            $this->command->error('===========================================');
            $this->command->error('Error: ' . $e->getMessage());
            $this->command->error('File: ' . $e->getFile());
            $this->command->error('Line: ' . $e->getLine());
            Log::error('APZ Data Seeding Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function seedFromCSV(string $csvPath): int
    {
        $this->command->info('Reading CSV file...');

        $file = fopen($csvPath, 'r');
        if ($file === false) {
            throw new Exception("Failed to open CSV file: {$csvPath}");
        }

        // Read header row
        $headers = fgetcsv($file, 0, ';');
        if ($headers === false) {
            fclose($file);
            throw new Exception('Failed to read CSV headers');
        }

        // Parse header to get period dates
        $factPeriods = [];
        $planPeriods = [];

        // Fact columns: 19-40 (indexes 18-39)
        for ($i = 18; $i <= 39 && $i < count($headers); $i++) {
            $factPeriods[] = trim($headers[$i]);
        }

        // Plan columns: 42-103 (indexes 41-102)
        for ($i = 41; $i <= 102 && $i < count($headers); $i++) {
            $planPeriods[] = trim($headers[$i]);
        }

        $contractCount = 0;
        $lineNumber = 1;

        $this->command->info("Found " . count($factPeriods) . " fact periods and " . count($planPeriods) . " plan periods");

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            $lineNumber++;

            try {
                // Skip rows with insufficient data
                if (count($row) < 20) {
                    continue;
                }

                // Skip empty rows
                $contractNumber = trim($row[3] ?? '');
                if (empty($contractNumber)) {
                    continue;
                }

                $companyName = trim($row[2] ?? '');
                if (empty($companyName)) {
                    continue;
                }

                // Create contract
                $contract = $this->createContract($row, $lineNumber);
                if (!$contract) {
                    continue;
                }

                // Create payment schedules from FACT columns (18-39)
                $this->createPaymentSchedules($contract, $row, $factPeriods, $planPeriods, 18, 41);

                $contractCount++;

            } catch (Exception $e) {
                $this->errors[] = "Line {$lineNumber}: " . $e->getMessage();
                Log::error("Failed to seed line {$lineNumber}", ['error' => $e->getMessage()]);
            }
        }

        fclose($file);

        $this->command->info("\n✓ Processed {$contractCount} contracts");
        return $contractCount;
    }

    private function createContract(array $row, int $lineNumber): ?Contract
    {
        try {
            $inn = $this->cleanIdentifier($row[0] ?? '');
            $pinfl = $this->cleanIdentifier($row[1] ?? '');
            $companyName = trim($row[2] ?? '');
            $contractNumber = trim($row[3] ?? '');
            $additionalNumber = trim($row[4] ?? '0');
            $status = $this->parseStatus($row[5] ?? '');
            $contractDate = $this->parseDate($row[6] ?? '');
            $completionDate = $this->parseDate($row[7] ?? '');
            $paymentTerms = trim($row[8] ?? '');
            $paymentPeriod = (int)($row[9] ?? 0);
            $advancePercent = trim($row[10] ?? '');
            $district = trim($row[11] ?? 'Unknown');
            $contractAmount = $this->parseAmount($row[12] ?? '0');
            $oneTimePayment = $this->parseAmount($row[13] ?? '0');
            $monthlyPayment = $this->parseAmount($row[14] ?? '0');
            $totalPayment = $this->parseAmount($row[15] ?? '0');
            $remainingAmount = $this->parseAmount($row[16] ?? '0');
            $totalFact = $this->parseAmount($row[17] ?? '0');

            return Contract::create([
                'inn' => $inn,
                'pinfl' => $pinfl,
                'company_name' => $companyName,
                'contract_number' => $contractNumber,
                'additional_contract_number' => $additionalNumber,
                'status' => $status,
                'contract_date' => $contractDate,
                'completion_date' => $completionDate,
                'payment_terms' => $paymentTerms,
                'payment_period' => $paymentPeriod,
                'advance_percent' => $advancePercent,
                'district' => $district,
                'contract_amount' => $contractAmount,
                'one_time_payment' => $oneTimePayment,
                'monthly_payment' => $monthlyPayment,
                'total_payment' => $totalPayment,
                'remaining_amount' => $remainingAmount,
                'total_fact' => $totalFact,
                'total_plan' => 0, // Will be calculated from schedules
            ]);

        } catch (Exception $e) {
            $this->warnings[] = "Line {$lineNumber}: Failed to create contract - " . $e->getMessage();
            return null;
        }
    }

    private function createPaymentSchedules(
        Contract $contract,
        array $row,
        array $factPeriods,
        array $planPeriods,
        int $factStartIndex,
        int $planStartIndex
    ): void {
        $totalPlan = 0;
        $schedules = [];

        // First, process PLAN columns (41-102) to establish baseline
        foreach ($planPeriods as $index => $periodHeader) {
            $colIndex = $planStartIndex + $index;
            if ($colIndex >= count($row)) break;

            $plannedAmount = $this->parseAmount($row[$colIndex] ?? '0');
            if ($plannedAmount <= 0) continue;

            $periodDate = $this->parsePeriodDate($periodHeader);
            if (!$periodDate) continue;

            $key = $periodDate->format('Y-m');
            $schedules[$key] = [
                'contract_id' => $contract->id,
                'year' => $periodDate->year,
                'month' => $periodDate->month,
                'period_date' => $periodDate->format('Y-m-d'),
                'period_label' => $periodDate->format('Y-m'),
                'planned_amount' => $plannedAmount,
                'actual_amount' => 0,
                'debt_amount' => $plannedAmount,
                'is_overdue' => $periodDate->isPast(),
            ];

            $totalPlan += $plannedAmount;
        }

        // Then, process FACT columns (18-39) and merge with plan data
        foreach ($factPeriods as $index => $periodHeader) {
            $colIndex = $factStartIndex + $index;
            if ($colIndex >= count($row)) break;

            $actualAmount = $this->parseAmount($row[$colIndex] ?? '0');
            if ($actualAmount <= 0) continue;

            $periodDate = $this->parsePeriodDate($periodHeader);
            if (!$periodDate) continue;

            $key = $periodDate->format('Y-m');

            if (isset($schedules[$key])) {
                // Update existing plan with actual data
                $schedules[$key]['actual_amount'] = $actualAmount;
                $schedules[$key]['debt_amount'] = $schedules[$key]['planned_amount'] - $actualAmount;
            } else {
                // Create FACT-only entry (no plan exists)
                $schedules[$key] = [
                    'contract_id' => $contract->id,
                    'year' => $periodDate->year,
                    'month' => $periodDate->month,
                    'period_date' => $periodDate->format('Y-m-d'),
                    'period_label' => $periodDate->format('Y-m'),
                    'planned_amount' => 0,
                    'actual_amount' => $actualAmount,
                    'debt_amount' => -$actualAmount,
                    'is_overdue' => false,
                ];
            }
        }

        // Bulk insert all schedules
        foreach ($schedules as $schedule) {
            PaymentSchedule::create($schedule);
        }

        // Update contract with total plan
        $contract->update(['total_plan' => $totalPlan]);
    }

    private function parseAmount($str): float
    {
        $str = trim($str);

        if (empty($str) || in_array($str, ['-', '–', '—', 'N/A', 'n/a'])) {
            return 0;
        }

        // Replace comma with dot
        $str = str_replace(',', '.', $str);

        // Remove spaces
        $str = str_replace([' ', '\xC2\xA0', '\s'], '', $str);

        // Remove percentage signs if any
        $str = str_replace('%', '', $str);

        // Remove any remaining non-numeric characters except decimal point and minus
        $str = preg_replace('/[^0-9.\-]/', '', $str);

        try {
            return (float)$str;
        } catch (Exception $e) {
            return 0;
        }
    }

    private function parseDate($str): ?string
    {
        $str = trim($str);
        if (empty($str)) return null;

        try {
            // Try DD.MM.YYYY format
            if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $str)) {
                return Carbon::createFromFormat('d.m.Y', $str)->format('Y-m-d');
            }
            // Try YYYY-MM-DD format
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
                return Carbon::parse($str)->format('Y-m-d');
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function parsePeriodDate($str): ?Carbon
    {
        $str = trim($str);
        if (empty($str)) return null;

        try {
            // Try DD.MM.YY format (e.g., "30.04.24")
            if (preg_match('/^\d{2}\.\d{2}\.\d{2}$/', $str)) {
                return Carbon::createFromFormat('d.m.y', $str);
            }
            // Try DD.MM.YYYY format
            if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $str)) {
                return Carbon::createFromFormat('d.m.Y', $str);
            }
            return null;
        } catch (Exception $e) {
            Log::warning("Failed to parse period date: {$str}");
            return null;
        }
    }

    private function parseStatus($str): string
    {
        $str = mb_strtolower(trim($str));

        if (strpos($str, 'амал') !== false) return 'амал қилувчи';
        if (strpos($str, 'бекор') !== false) return 'Бекор қилинган';
        if (strpos($str, 'якун') !== false) return 'Якунланган';

        return 'амал қилувчи';
    }

    private function cleanIdentifier($str): ?string
    {
        $str = preg_replace('/[^0-9]/', '', trim($str));
        return empty($str) ? null : $str;
    }

    private function displaySummary(int $contractCount, float $duration): void
    {
        $this->command->newLine();
        $this->command->info('===========================================');
        $this->command->info('SEEDING COMPLETED SUCCESSFULLY!');
        $this->command->info('===========================================');
        $this->command->info("Contracts imported: {$contractCount}");
        $this->command->info("Duration: {$duration} seconds");

        $scheduleCount = PaymentSchedule::count();
        $this->command->info("Payment schedules created: {$scheduleCount}");

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
}
