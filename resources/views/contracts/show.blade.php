@extends('layouts.app')

@section('title', 'Шартнома маълумотлари')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <a href="{{ route('contracts.index') }}" class="btn btn-outline-primary me-3">
                        <i class="fas fa-arrow-left me-2"></i>Орқага
                    </a>
                    <div>
                        <h1 class="display-6 fw-bold text-blue mb-0">
                            <i class="fas fa-file-contract me-2"></i>{{ $contract->contract_number }}
                        </h1>
                        <p class="text-muted mb-0">{{ $contract->company_name }}</p>
                    </div>
                </div>
                <div>
                    @if($contract->status === config('dashboard.statuses.active'))
                        <span class="badge bg-blue text-white fs-6 px-3 py-2">
                            <i class="fas fa-check-circle me-1"></i>Амалдаги
                        </span>
                    @elseif($contract->status === config('dashboard.statuses.completed'))
                        <span class="badge bg-blue text-white fs-6 px-3 py-2">
                            <i class="fas fa-check-double me-1"></i>Якунланган
                        </span>
                    @elseif($contract->status === config('dashboard.statuses.cancelled'))
                        <span class="badge bg-danger text-white fs-6 px-3 py-2">
                            <i class="fas fa-ban me-1"></i>Бекор қилинган
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Contract Details -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-blue text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Асосий маълумотлар</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th class="text-muted" style="width: 40%;">Шартнома рақами:</th>
                            <td class="fw-bold">{{ $contract->contract_number }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Корхона:</th>
                            <td class="fw-bold">{{ $contract->company_name }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Ҳудуд:</th>
                            <td><i class="fas fa-map-marker-alt text-blue me-1"></i>{{ $contract->district }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Сана:</th>
                            <td>{{ \Carbon\Carbon::parse($contract->contract_date)->format('d.m.Y') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Шартнома суммаси:</th>
                            <td class="text-blue fw-bold fs-5">{{ number_format($contract->contract_amount / 1000000000, 2) }} млрд сўм</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-blue text-white">
                    <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Тўловлар ҳисоби</h5>
                </div>
                <div class="card-body">
                    @php
                        $totalPaid = $contract->payments->sum('amount_debit') ?? 0;
                        $debt = $contract->contract_amount - $totalPaid;
                        $percentage = $contract->contract_amount > 0 ? ($totalPaid / $contract->contract_amount * 100) : 0;
                    @endphp
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th class="text-muted" style="width: 40%;">Тўланган:</th>
                            <td class="text-blue fw-bold">{{ number_format($totalPaid / 1000000000, 2) }} млрд сўм</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Қарз:</th>
                            <td class="text-red fw-bold">{{ number_format($debt / 1000000000, 2) }} млрд сўм</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Тўлов фоизи:</th>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-3" style="height: 25px;">
                                        <div class="progress-bar bg-blue" role="progressbar"
                                             style="width: {{ $percentage }}%"
                                             aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                            {{ number_format($percentage, 1) }}%
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Тўловлар сони:</th>
                            <td class="fw-bold">{{ $contract->payments->count() }} та</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments History -->
    @if($contract->payments->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-blue text-white">
                    <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Тўловлар тарихи ({{ $contract->payments->count() }} та)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>№</th>
                                    <th>Тўлов санаси</th>
                                    <th>Сумма</th>
                                    <th>Ҳисоб-китоб</th>
                                    <th>Изоҳ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contract->payments->sortByDesc('payment_date') as $payment)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d.m.Y') }}</td>
                                    <td class="text-blue fw-bold">{{ number_format($payment->amount_debit / 1000000, 2) }} млн</td>
                                    <td>
                                        <small class="text-muted">
                                            Дебет: {{ number_format($payment->amount_debit, 0) }}<br>
                                            Кредит: {{ number_format($payment->amount_credit, 0) }}
                                        </small>
                                    </td>
                                    <td>{{ $payment->payment_basis ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Payment Schedule -->
    @if($contract->paymentSchedules->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-blue text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Тўлов графиги ({{ $contract->paymentSchedules->count() }} та)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>№</th>
                                    <th>Тўлов санаси</th>
                                    <th>Режалаштирилган сумма</th>
                                    <th>Изоҳ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contract->paymentSchedules->sortBy('due_date') as $schedule)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ \Carbon\Carbon::parse($schedule->due_date)->format('d.m.Y') }}</td>
                                    <td class="fw-bold">{{ number_format($schedule->planned_amount / 1000000, 2) }} млн</td>
                                    <td>{{ $schedule->period ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
