<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class ContractService
{
    public function parseContractCSV($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception("Contract CSV file not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new Exception("Contract CSV file is not readable: {$filePath}");
        }

        $file = fopen($filePath, 'r');

        if ($file === false) {
            throw new Exception("Failed to open contract CSV file: {$filePath}");
        }

        try {
            // Read and validate headers
            $headers = fgetcsv($file, 0, ';');
            if ($headers === false || count($headers) < 17) {
                throw new Exception("Invalid CSV header format in contracts file");
            }

            $contracts = [];
            $lineNumber = 1; // Start from 1 (header is line 1)

            while (($row = fgetcsv($file, 0, ';')) !== false) {
                $lineNumber++;

                try {
                    // Skip rows with insufficient data
                    if (count($row) < 10) {
                        Log::warning("Contract CSV line {$lineNumber}: Insufficient columns, skipping");
                        continue;
                    }

                    $contractNumber = trim($row[4] ?? '');
                    $companyName = trim($row[3] ?? '');

                    // Skip if no contract number or company name
                    if (empty($contractNumber)) {
                        Log::warning("Contract CSV line {$lineNumber}: Missing contract number, skipping");
                        continue;
                    }

                    if (empty($companyName)) {
                        Log::warning("Contract CSV line {$lineNumber}: Missing company name, skipping");
                        continue;
                    }

                    $contractAmount = $this->parseAmount($row[13] ?? '0');
                    $initialPayment = $this->parseAmount($row[14] ?? '0');

                    // Validate amounts
                    if ($contractAmount < 0) {
                        Log::warning("Contract CSV line {$lineNumber}: Negative contract amount, setting to 0");
                        $contractAmount = 0;
                    }

                    if ($initialPayment < 0) {
                        Log::warning("Contract CSV line {$lineNumber}: Negative initial payment, setting to 0");
                        $initialPayment = 0;
                    }

                    // Parse quarterly payment data from columns 17 onwards
                    // Extended to support up to column 47 (2024-2030, 7 years * 4 quarters = 28 columns)
                    $quarterlyPayments = [];
                    for ($i = 17; $i <= 47 && $i < count($row); $i++) {
                        $quarterlyPayments[] = $this->parseAmount($row[$i] ?? '0');
                    }

                    $contracts[] = [
                        'contract_number' => $contractNumber,
                        'inn' => $this->cleanIdentifier($row[1] ?? ''),
                        'pinfl' => $this->cleanIdentifier($row[1] ?? ''),
                        'passport' => trim($row[2] ?? ''),
                        'company_name' => $companyName,
                        'district' => trim($row[12] ?? 'Unknown'),
                        'status' => $this->parseStatus($row[6] ?? ''),
                        'contract_date' => $this->parseDate($row[7] ?? ''),
                        'completion_date' => $this->parseDate($row[8] ?? ''),
                        'contract_amount' => $contractAmount,
                        'initial_payment' => $initialPayment,
                        'remaining_amount' => max(0, $this->parseAmount($row[15] ?? '0')),
                        'quarterly_payment' => max(0, $this->parseAmount($row[16] ?? '0')),
                        'payment_terms' => trim($row[9] ?? ''),
                        'payment_period' => max(0, (int)($row[10] ?? 0)),
                        'advance_percent' => $this->parsePercent($row[11] ?? '0'),
                        'quarterly_payments' => $quarterlyPayments, // Store quarterly payments data
                    ];

                } catch (Exception $e) {
                    Log::error("Contract CSV line {$lineNumber} error: " . $e->getMessage());
                    continue; // Skip this row and continue with next
                }
            }

            fclose($file);

            if (empty($contracts)) {
                throw new Exception("No valid contracts found in CSV file");
            }

            return $contracts;

        } catch (Exception $e) {
            fclose($file);
            throw $e;
        }
    }

    public function parsePaymentCSV($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception("Payment CSV file not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new Exception("Payment CSV file is not readable: {$filePath}");
        }

        $file = fopen($filePath, 'r');

        if ($file === false) {
            throw new Exception("Failed to open payment CSV file: {$filePath}");
        }

        try {
            // Read and validate headers
            $headers = fgetcsv($file, 0, ';');
            if ($headers === false) {
                throw new Exception("Invalid CSV header format in payments file");
            }

            $payments = [];
            $lineNumber = 1; // Start from 1 (header is line 1)

            while (($row = fgetcsv($file, 0, ';')) !== false) {
                $lineNumber++;

                try {
                    // Skip rows with insufficient data
                    if (count($row) < 3) {
                        Log::warning("Payment CSV line {$lineNumber}: Insufficient columns, skipping");
                        continue;
                    }

                    $paymentDate = $this->parseDate($row[0] ?? '');
                    if (!$paymentDate) {
                        Log::warning("Payment CSV line {$lineNumber}: Invalid payment date, skipping");
                        continue;
                    }

                    $amount = $this->parseAmount($row[2] ?? '0');
                    if ($amount <= 0) {
                        Log::warning("Payment CSV line {$lineNumber}: Zero or negative amount, skipping");
                        continue;
                    }

                    $inn = $this->cleanIdentifier($row[1] ?? '');

                    $payments[] = [
                        'payment_date' => $paymentDate,
                        'inn' => $inn,
                        'amount_debit' => $amount,
                        'amount_credit' => 0,
                        'description' => trim($row[3] ?? ''),
                        'district' => trim($row[6] ?? ''),
                        'payment_type' => trim($row[7] ?? ''),
                        'year' => isset($row[8]) && is_numeric($row[8]) ? (int)$row[8] : (int)date('Y'),
                        'month' => trim($row[5] ?? ''),
                        'is_matched' => false,
                    ];

                } catch (Exception $e) {
                    Log::error("Payment CSV line {$lineNumber} error: " . $e->getMessage());
                    continue; // Skip this row and continue with next
                }
            }

            fclose($file);

            if (empty($payments)) {
                Log::warning("No valid payments found in CSV file");
            }

            return $payments;

        } catch (Exception $e) {
            fclose($file);
            throw $e;
        }
    }

    private function parseAmount($str)
    {
        $str = trim($str);

        // Handle empty or dash-only values
        if (empty($str) || in_array($str, ['-', '–', '—', 'N/A', 'n/a'])) {
            return 0;
        }

        // Remove spaces (regular space, non-breaking space, thin space)
        $str = str_replace([' ', ',', ' ', ' ', '\xC2\xA0'], '', $str);

        // Handle dash characters
        $str = str_replace(['-', '–', '—'], '0', $str);

        // Remove any remaining non-numeric characters except decimal point
        $str = preg_replace('/[^0-9.]/', '', $str);

        try {
            $value = (float)$str;
            return $value >= 0 ? $value : 0; // Ensure non-negative
        } catch (Exception $e) {
            Log::warning("Failed to parse amount: {$str}");
            return 0;
        }
    }

    private function parseDate($str)
    {
        $str = trim($str);
        if (empty($str) || $str == '00.01.1900') return null;

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
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseStatus($str)
    {
        $str = trim($str);

        // Convert to lowercase for case-insensitive matching
        $strLower = mb_strtolower($str);

        if (strpos($strLower, 'амал') !== false) return 'Амал қилувчи';
        if (strpos($strLower, 'бекор') !== false) return 'Бекор қилинган';
        if (strpos($strLower, 'якун') !== false) return 'Якунланган';

        // Default to active status
        return 'Амал қилувчи';
    }

    private function parsePercent($str)
    {
        return (float)str_replace('%', '', trim($str));
    }

    private function cleanIdentifier($str)
    {
        $str = trim($str);

        // Remove all non-numeric characters
        $str = preg_replace('/[^0-9]/', '', $str);

        // Return null if empty or if it's too short to be a valid identifier
        if (empty($str)) {
            return null;
        }

        return $str;
    }

    public function matchPaymentsToContracts()
    {
        $unmatchedPayments = Payment::where('is_matched', false)->get();

        foreach ($unmatchedPayments as $payment) {
            $contracts = $this->findMatchingContracts($payment);

            if ($contracts->count() == 1) {
                $payment->contract_id = $contracts->first()->id;
                $payment->is_matched = true;
                $payment->save();
            } elseif ($contracts->count() > 1) {
                $bestMatch = $this->selectBestContract($payment, $contracts);
                if ($bestMatch) {
                    $payment->contract_id = $bestMatch->id;
                    $payment->is_matched = true;
                    $payment->save();
                }
            }
        }
    }

    private function findMatchingContracts(Payment $payment)
    {
        $query = Contract::query();

        if ($payment->inn) {
            $query->where('inn', $payment->inn);
        } elseif ($payment->pinfl) {
            $query->where('pinfl', $payment->pinfl);
        } elseif ($payment->passport) {
            $query->where('passport', $payment->passport);
        }

        return $query->get();
    }

    private function selectBestContract(Payment $payment, $contracts)
    {
        $bestScore = 0;
        $bestContract = null;

        foreach ($contracts as $contract) {
            $score = 0;

            // Date proximity
            if ($contract->contract_date && $payment->payment_date->gte($contract->contract_date)) {
                $score += 3;
            }

            // District match
            if ($contract->district == $payment->district) {
                $score += 2;
            }

            // Active status
            if ($contract->status == 'Амал қилувчи') {
                $score += 1;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestContract = $contract;
            }
        }

        return $bestContract;
    }

    public function generatePaymentSchedules(Contract $contract)
    {
        $schedules = [];
        $quarterlyAmount = $contract->quarterly_payment;

        // Parse schedule from CSV columns (Q1-Q4 for years 2024-2027)
        // This is simplified - in real implementation, parse from CSV columns 17-35

        $years = [2024, 2025, 2026, 2027];
        foreach ($years as $year) {
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $schedules[] = [
                    'contract_id' => $contract->id,
                    'year' => $year,
                    'quarter' => $quarter,
                    'period' => "{$year} Q{$quarter}",
                    'planned_amount' => $quarterlyAmount,
                    'actual_amount' => 0,
                    'debt_amount' => $quarterlyAmount,
                    'due_date' => Carbon::create($year, $quarter * 3, 1)->endOfMonth(),
                ];
            }
        }

        return $schedules;
    }

    public function calculateDebtAndOverdue()
    {
        $schedules = PaymentSchedule::with('contract.payments')->get();

        foreach ($schedules as $schedule) {
            // Get payments for this year
            $yearPayments = $schedule->contract->payments()
                ->whereYear('payment_date', $schedule->year);

            // Filter by quarter if specified
            if ($schedule->quarter) {
                $yearPayments = $yearPayments->get()->filter(function ($payment) use ($schedule) {
                    $quarter = ceil($payment->payment_date->month / 3);
                    return $quarter == $schedule->quarter;
                });
                $payments = $yearPayments->sum('amount_debit');
            } else {
                $payments = $yearPayments->sum('amount_debit');
            }

            $schedule->actual_amount = $payments;
            $schedule->debt_amount = $schedule->planned_amount - $payments;
            $schedule->checkOverdue();
            $schedule->save();
        }
    }
}
