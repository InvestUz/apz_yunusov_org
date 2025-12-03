<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use Carbon\Carbon;

class ContractService
{
    public function parseContractCSV($filePath)
    {
        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file, 0, ';');
        $contracts = [];

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            if (count($row) < 10) continue;

            $contractNumber = trim($row[4] ?? '');
            $companyName = trim($row[3] ?? '');

            // Skip if no contract number or company name
            if (empty($contractNumber) || empty($companyName)) continue;

            $contractAmount = $this->parseAmount($row[13] ?? '0');
            $initialPayment = $this->parseAmount($row[14] ?? '0');

            // Parse quarterly payment data from columns 17-35
            $quarterlyPayments = [];
            for ($i = 17; $i <= 35 && $i < count($row); $i++) {
                $quarterlyPayments[] = $this->parseAmount($row[$i] ?? '0');
            }

            $contracts[] = [
                'contract_number' => $contractNumber,
                'inn' => $this->cleanIdentifier($row[1] ?? ''),
                'pinfl' => $this->cleanIdentifier($row[1] ?? ''),
                'passport' => trim($row[2] ?? ''),
                'company_name' => trim($row[3] ?? 'N/A'),
                'district' => trim($row[12] ?? 'Unknown'),
                'status' => $this->parseStatus($row[6] ?? ''),
                'contract_date' => $this->parseDate($row[7] ?? ''),
                'completion_date' => $this->parseDate($row[8] ?? ''),
                'contract_amount' => $contractAmount,
                'initial_payment' => $initialPayment,
                'remaining_amount' => $this->parseAmount($row[15] ?? '0'),
                'quarterly_payment' => $this->parseAmount($row[16] ?? '0'),
                'payment_terms' => trim($row[9] ?? ''),
                'payment_period' => (int)($row[10] ?? 0),
                'advance_percent' => $this->parsePercent($row[11] ?? '0'),
                'quarterly_payments' => $quarterlyPayments, // Store quarterly payments data
            ];
        }

        fclose($file);
        return $contracts;
    }

    public function parsePaymentCSV($filePath)
    {
        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file, 0, ';');
        $payments = [];

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            if (count($row) < 5) continue;

            $paymentDate = $this->parseDate($row[0] ?? '');
            if (!$paymentDate) continue;

            $amount = $this->parseAmount($row[2] ?? '0');
            if ($amount == 0) continue;

            $payments[] = [
                'payment_date' => $paymentDate,
                'inn' => $this->cleanIdentifier($row[1] ?? ''),
                'amount_debit' => $amount,
                'amount_credit' => 0,
                'description' => trim($row[3] ?? ''),
                'district' => trim($row[6] ?? ''),
                'payment_type' => trim($row[7] ?? ''),
                'year' => (int)($row[8] ?? date('Y')),
                'month' => trim($row[5] ?? ''),
            ];
        }

        fclose($file);
        return $payments;
    }

    private function parseAmount($str)
    {
        $str = trim($str);
        $str = str_replace([' ', ',', ' ', ' '], '', $str);
        $str = str_replace(['-', '–', '—'], '0', $str);
        return (float)$str;
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
        if (strpos($str, 'Амал') !== false) return 'Амал қилувчи';
        if (strpos($str, 'Бекор') !== false) return 'Бекор қилинган';
        if (strpos($str, 'Якун') !== false) return 'Якунланган';
        return 'Амал қилувчи';
    }

    private function parsePercent($str)
    {
        return (float)str_replace('%', '', trim($str));
    }

    private function cleanIdentifier($str)
    {
        $str = trim($str);
        $str = preg_replace('/[^0-9]/', '', $str);
        return !empty($str) ? $str : null;
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
