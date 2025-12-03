@extends('layouts.app')

@section('title', 'ART Monitoring — Шартнома ва Факт Интеграция')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="d-flex align-items-center">
                <div class="icon-container me-4" style="width: 72px; height: 72px; background: linear-gradient(135deg, var(--blue-primary), var(--blue-secondary)); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-chart-line text-white" style="font-size: 2rem;"></i>
                </div>
                <div>
                    <h1 class="display-5 fw-bold text-blue mb-1" style="letter-spacing: -0.025em;">АРТ Мониторинг Тизими</h1>
                    <p class="text-muted mb-0" style="font-size: 1.1rem;">Шартнома ва Факт Интеграция — Барча тўлов турлари</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-5">
        <!-- Card 1: Total Contracts - Blue -->
        <div class="col-xl-20p col-lg-4 col-md-6">
            <div class="stats-card card-blue">
                <div class="card-body w-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1">
                            <div class="card-title">Жами лотлар сони</div>
                            <div class="card-value text-blue">{{ $stats->totalContracts }} <span style="font-size: 1.5rem; font-weight: 500;">ta</span></div>
                            <div class="card-subtitle mt-3">
                                <strong class="text-blue" style="font-size: 1.2rem;">{{ number_format($stats->totalAmount / 1000000000, 2) }}</strong> млрд сўм
                            </div>
                        </div>
                        <div class="icon-container">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Total Paid - Blue -->
        <div class="col-xl-20p col-lg-4 col-md-6">
            <div class="stats-card card-blue">
                <div class="card-body w-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1">
                            <div class="card-title">Тушадиган маблағ</div>
                            <div class="card-value text-blue">{{ number_format($stats->totalPaid / 1000000000, 2) }}</div>
                            <div class="card-subtitle mt-3">млрд сўм</div>
                        </div>
                        <div class="icon-container">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Active Contracts - Blue -->
        <div class="col-xl-20p col-lg-4 col-md-6">
            <div class="stats-card card-blue">
                <div class="card-body w-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1">
                            <div class="card-title">Амалда тушган маблағ</div>
                            <div class="card-value text-blue">{{ number_format($stats->activeAmount / 1000000000, 2) }}</div>
                            <div class="card-subtitle mt-3">млрд сўм</div>
                        </div>
                        <div class="icon-container">
                            <i class="fas fa-calculator"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Total Debt - Red with Progress -->
        <div class="col-xl-20p col-lg-4 col-md-6">
            <div class="stats-card card-red">
                <div class="card-body w-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1">
                            <div class="card-title">Қолдиқ маблағ</div>
                            <div class="card-value text-red">{{ number_format($stats->totalDebt / 1000000000, 2) }}</div>
                            <div class="card-subtitle mt-3">млрд сўм</div>
                            @php
                                $debtPercentage = $stats->totalAmount > 0 ? ($stats->totalDebt / $stats->totalAmount * 100) : 0;
                            @endphp
                            {{-- <div class="progress-bar-custom mt-3">
                                <div class="progress-fill red" style="width: {{ min($debtPercentage, 100) }}%"></div>
                            </div>
                            <div class="text-end mt-2">
                                <small class="text-red fw-bold" style="font-size: 1rem;">{{ number_format($debtPercentage, 1) }}%</small>
                            </div> --}}
                        </div>
                        <div class="icon-container">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 5: Overdue Debt - Red with Progress -->
        <div class="col-xl-20p col-lg-4 col-md-6">
            <div class="stats-card card-red">
                <div class="card-body w-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1">
                            <div class="card-title">Муддати ўтган қарздорлик</div>
                            <div class="card-value text-red">{{ number_format(($stats->totalDebt * 0.54) / 1000000000, 2) }}</div>
                            <div class="card-subtitle mt-3">млрд сўм</div>
                        </div>
                        <div class="icon-container">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-blue text-white" style="padding: 1.25rem 1.5rem;">
                    <h5 class="mb-0" style="font-size: 1.15rem;"><i class="fas fa-map-marked-alt me-2"></i>Ҳудуд бўйича тақсимлаш</h5>
                </div>
                <div class="card-body" style="padding: 1.75rem;">
                    <canvas id="districtChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-blue text-white" style="padding: 1.25rem 1.5rem;">
                    <h5 class="mb-0" style="font-size: 1.15rem;"><i class="fas fa-chart-line me-2"></i>Режа — Факт — Қарздорлик</h5>
                </div>
                <div class="card-body" style="padding: 1.75rem;">
                    <canvas id="scheduleChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Monitoring Table -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-blue text-white" style="padding: 1.5rem 2rem;">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0" style="font-size: 1.35rem;">
                            <i class="fas fa-table me-3"></i>Ҳудудлар буйича мониторинг жадвали
                        </h5>
                        <span class="badge bg-white text-blue fs-6 px-3 py-2">{{ $districtStats->count() }} та худуд</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped monitoring-table mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th rowspan="2" class="align-middle">Т/р</th>
                                    <th rowspan="2" class="align-middle">Ҳудуд</th>
                                    <th colspan="2">Жами АРТ шартномалар</th>
                                    <th colspan="2" style="display: none;">Бекор қилинган</th>
                                    <th colspan="2">Тўлиқ тўланган</th>
                                    <th colspan="2">Амалдаги шартномалар</th>
                                    <th colspan="3">2025 III чорак</th>
                                    <th colspan="3">2025 IV чорак</th>
                                    <th colspan="3">2026 йил</th>
                                    <th colspan="3">2027 йил</th>
                                </tr>
                                <tr>
                                    <th>сони</th>
                                    <th>млрд сўм</th>
                                    <th style="display: none;">сони</th>
                                    <th style="display: none;">млрд</th>
                                    <th>сони</th>
                                    <th>млрд</th>
                                    <th>сони</th>
                                    <th>млрд</th>
                                    <th>Режа</th>
                                    <th>Факт</th>
                                    <th>Қарз</th>
                                    <th>Режа</th>
                                    <th>Факт</th>
                                    <th>Қарз</th>
                                    <th>Режа</th>
                                    <th>Факт</th>
                                    <th>Қарз</th>
                                    <th>Режа</th>
                                    <th>Факт</th>
                                    <th>Қарз</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($districtStats as $index => $district)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="district-col clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}'" title="Барча шартномаларни кўриш">
                                        <i class="fas fa-map-marker-alt me-1 text-blue"></i>{{ $district->districtName }}
                                    </td>

                                    <!-- Жами АРТ шартномалар -->
                                    <td class="clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}'">{{ $district->contractsCount }}</td>
                                    <td class="amount-billion clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}'">{{ number_format($district->totalAmount / 1000000000, 2) }}</td>

                                    <!-- Бекор қилинган (other statuses, hidden) -->
                                    <td class="clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&status=cancelled'" style="display: none;">{{ $district->cancelledCount }}</td>
                                    <td class="clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&status=cancelled'" style="display: none;">{{ number_format($district->cancelledAmount / 1000000000, 2) }}</td>

                                    <!-- Тўлиқ тўланған -->
                                    <td class="clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&status=completed'">{{ $district->completedCount }}</td>
                                    <td class="clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&status=completed'">{{ number_format($district->completedAmount / 1000000000, 2) }}</td>

                                    <!-- Амалдаги шартномалар -->
                                    <td class="clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&status=active'">{{ $district->activeCount }}</td>
                                    <td class="text-blue clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&status=active'">{{ number_format($district->activeAmount / 1000000000, 2) }}</td>

                                    <!-- 2025 III чорак -->
                                    <td class="clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&quarter=2025-3&type=plan'">{{ number_format($district->q3_2025_plan / 1000000000, 2) }}</td>
                                    <td class="text-blue clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&quarter=2025-3&type=fact'">{{ number_format($district->q3_2025_fact / 1000000000, 2) }}</td>
                                    <td class="text-red clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&quarter=2025-3&type=debt'">{{ number_format($district->q3_2025_debt / 1000000000, 2) }}</td>

                                    <!-- 2025 IV чорак -->
                                    <td class="clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&quarter=2025-4&type=plan'">{{ number_format($district->q4_2025_plan / 1000000000, 2) }}</td>
                                    <td class="text-blue clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&quarter=2025-4&type=fact'">{{ number_format($district->q4_2025_fact / 1000000000, 2) }}</td>
                                    <td class="text-red clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&quarter=2025-4&type=debt'">{{ number_format($district->q4_2025_debt / 1000000000, 2) }}</td>

                                    <!-- 2026 йил -->
                                    <td class="clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&year=2026&type=plan'">{{ number_format($district->year_2026_plan / 1000000000, 2) }}</td>
                                    <td class="text-blue clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&year=2026&type=fact'">{{ number_format($district->year_2026_fact / 1000000000, 2) }}</td>
                                    <td class="text-red clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&year=2026&type=debt'">{{ number_format($district->year_2026_debt / 1000000000, 2) }}</td>

                                    <!-- 2027 йил -->
                                    <td class="clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&year=2027&type=plan'">{{ number_format($district->year_2027_plan / 1000000000, 2) }}</td>
                                    <td class="text-blue clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&year=2027&type=fact'">{{ number_format($district->year_2027_fact / 1000000000, 2) }}</td>
                                    <td class="text-red clickable" onclick="window.location.href='/contracts?district={{ urlencode($district->districtName) }}&year=2027&type=debt'">{{ number_format($district->year_2027_debt / 1000000000, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="23" class="text-center py-5">
                                        <i class="fas fa-inbox text-muted" style="font-size: 3rem;"></i>
                                        <h5 class="mt-3 text-muted">Худуд маълумотлари топилмади</h5>
                                        <p class="text-muted">Шартнома маълумотларини текширинг</p>
                                    </td>
                                </tr>
                                @endforelse

                                @if($districtStats->count() > 0)
                                @php
                                    // Calculate totals for Toshkent shahri
                                    $totalContracts = $districtStats->sum('contractsCount');
                                    $totalAmount = $districtStats->sum('totalAmount');
                                    $totalCancelled = $districtStats->sum('cancelledCount');
                                    $totalCancelledAmount = $districtStats->sum('cancelledAmount');
                                    $totalCompleted = $districtStats->sum('completedCount');
                                    $totalCompletedAmount = $districtStats->sum('completedAmount');
                                    $totalActive = $districtStats->sum('activeCount');
                                    $totalActiveAmount = $districtStats->sum('activeAmount');

                                    // Q3 2025 totals
                                    $q3_2025_plan_total = $districtStats->sum('q3_2025_plan');
                                    $q3_2025_fact_total = $districtStats->sum('q3_2025_fact');
                                    $q3_2025_debt_total = $districtStats->sum('q3_2025_debt');

                                    // Q4 2025 totals
                                    $q4_2025_plan_total = $districtStats->sum('q4_2025_plan');
                                    $q4_2025_fact_total = $districtStats->sum('q4_2025_fact');
                                    $q4_2025_debt_total = $districtStats->sum('q4_2025_debt');

                                    // 2026 totals
                                    $year_2026_plan_total = $districtStats->sum('year_2026_plan');
                                    $year_2026_fact_total = $districtStats->sum('year_2026_fact');
                                    $year_2026_debt_total = $districtStats->sum('year_2026_debt');

                                    // 2027 totals
                                    $year_2027_plan_total = $districtStats->sum('year_2027_plan');
                                    $year_2027_fact_total = $districtStats->sum('year_2027_fact');
                                    $year_2027_debt_total = $districtStats->sum('year_2027_debt');

                                    // Current date logic for debt display
                                    $now = \Carbon\Carbon::now();
                                    $currentYear = $now->year;
                                    $currentQuarter = $now->quarter;

                                    // Determine if periods are past/present (show debt) or future (show "-")
                                    $showQ3_2025Debt = ($currentYear > 2025) || ($currentYear == 2025 && $currentQuarter >= 3);
                                    $showQ4_2025Debt = ($currentYear > 2025) || ($currentYear == 2025 && $currentQuarter >= 4);
                                    $show2026Debt = $currentYear >= 2026;
                                    $show2027Debt = $currentYear >= 2027;
                                @endphp
                                <tr class="total-row">
                                    <td style="background: linear-gradient(135deg, var(--blue-primary), var(--blue-secondary)); color: white; font-weight: 700;"></td>
                                    <td class="text-start" style="background: linear-gradient(135deg, var(--blue-primary), var(--blue-secondary)); color: white;"><i class="fas fa-city me-2"></i>Тошкент шаҳри</td>

                                    <!-- Жами АРТ шартномалар -->
                                    <td>{{ $totalContracts }}</td>
                                    <td class="amount-billion">{{ number_format($totalAmount / 1000000000, 2) }}</td>

                                    <!-- Бекор қилинган -->
                                    <td>{{ $totalCancelled }}</td>
                                    <td>{{ number_format($totalCancelledAmount / 1000000000, 2) }}</td>

                                    <!-- Тўлиқ тўланган -->
                                    <td>{{ $totalCompleted }}</td>
                                    <td>{{ number_format($totalCompletedAmount / 1000000000, 2) }}</td>

                                    <!-- Амалдаги шартномалар -->
                                    <td>{{ $totalActive }}</td>
                                    <td class="text-blue">{{ number_format($totalActiveAmount / 1000000000, 2) }}</td>

                                    <!-- 2025 III чорак -->
                                    <td>{{ number_format($q3_2025_plan_total / 1000000000, 2) }}</td>
                                    <td class="text-blue">{{ number_format($q3_2025_fact_total / 1000000000, 2) }}</td>
                                    <td class="text-red">{{ $showQ3_2025Debt ? number_format($q3_2025_debt_total / 1000000000, 2) : '-' }}</td>

                                    <!-- 2025 IV чорак -->
                                    <td>{{ number_format($q4_2025_plan_total / 1000000000, 2) }}</td>
                                    <td class="text-blue">{{ number_format($q4_2025_fact_total / 1000000000, 2) }}</td>
                                    <td class="text-red">{{ $showQ4_2025Debt ? number_format($q4_2025_debt_total / 1000000000, 2) : '-' }}</td>

                                    <!-- 2026 йил -->
                                    <td>{{ number_format($year_2026_plan_total / 1000000000, 2) }}</td>
                                    <td class="text-blue">{{ number_format($year_2026_fact_total / 1000000000, 2) }}</td>
                                    <td class="text-red">{{ $show2026Debt ? number_format($year_2026_debt_total / 1000000000, 2) : '-' }}</td>

                                    <!-- 2027 йил -->
                                    <td>{{ number_format($year_2027_plan_total / 1000000000, 2) }}</td>
                                    <td class="text-blue">{{ number_format($year_2027_fact_total / 1000000000, 2) }}</td>
                                    <td class="text-red">{{ $show2027Debt ? number_format($year_2027_debt_total / 1000000000, 2) : '-' }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// District Chart with Blue & Red colors
const districtData = @json($districtStats->map(function($d) {
    return [
        'district' => $d->districtName,
        'total_amount' => $d->totalAmount,
        'paid_amount' => $d->paidAmount
    ];
}));

const districtChart = new Chart(document.getElementById('districtChart'), {
    type: 'bar',
    data: {
        labels: districtData.map(d => d.district),
        datasets: [{
            label: 'Жами сумма (млрд)',
            data: districtData.map(d => (d.total_amount / 1000000000).toFixed(2)),
            backgroundColor: '#2563eb',
        }, {
            label: 'Тўланган (млрд)',
            data: districtData.map(d => (d.paid_amount / 1000000000).toFixed(2)),
            backgroundColor: '#3b82f6',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Chart Data from API with Blue & Red colors
const chartData = @json($chartData);
const scheduleChart = new Chart(document.getElementById('scheduleChart'), {
    type: 'line',
    data: {
        labels: chartData.map(d => d.label || d.period),
        datasets: [{
            label: 'Тўловлар (млн)',
            data: chartData.map(d => (d.actual / 1000000).toFixed(2)),
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
@endsection
