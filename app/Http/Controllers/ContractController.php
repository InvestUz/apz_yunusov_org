<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function index(Request $request): View
    {
        $query = Contract::query()->with(['payments', 'paymentSchedules']);

        // Filter by district
        if ($request->has('district')) {
            $query->where('district', $request->get('district'));
        }

        // Filter by status
        if ($request->has('status')) {
            $statusMap = [
                'active' => config('dashboard.statuses.active'),
                'cancelled' => config('dashboard.statuses.cancelled'),
                'completed' => config('dashboard.statuses.completed'),
            ];

            $status = $request->get('status');
            if (isset($statusMap[$status])) {
                $query->where('status', $statusMap[$status]);
            }
        }

        // Filter by quarter
        if ($request->has('quarter') && $request->has('type')) {
            [$year, $quarter] = explode('-', $request->get('quarter'));
            $type = $request->get('type');

            if ($type === 'fact') {
                // Filter contracts that have payments in this quarter
                $query->whereHas('payments', function ($q) use ($year, $quarter) {
                    $startMonth = ($quarter - 1) * 3 + 1;
                    $endMonth = $quarter * 3;

                    $q->whereYear('payment_date', $year)
                      ->whereMonth('payment_date', '>=', $startMonth)
                      ->whereMonth('payment_date', '<=', $endMonth);
                });
            }
        }

        // Filter by year
        if ($request->has('year') && $request->has('type')) {
            $year = $request->get('year');
            $type = $request->get('type');

            if ($type === 'fact') {
                // Filter contracts that have payments in this year
                $query->whereHas('payments', function ($q) use ($year) {
                    $q->whereYear('payment_date', $year);
                });
            }
        }

        // Get paginated results
        $contracts = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get filter summary
        $filterSummary = [
            'district' => $request->get('district'),
            'status' => $request->get('status'),
            'quarter' => $request->get('quarter'),
            'year' => $request->get('year'),
            'type' => $request->get('type'),
            'total_count' => $contracts->total(),
            'total_amount' => $query->sum('contract_amount'),
        ];

        return view('contracts.index', compact('contracts', 'filterSummary'));
    }

    public function show(Contract $contract): View
    {
        $contract->load(['payments', 'paymentSchedules']);

        return view('contracts.show', compact('contract'));
    }
}
