@extends('layouts.app')

@section('title', 'Шартномалар рўйхати')

@section('content')
<div class="container-fluid">
    <!-- Page Header with Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <a href="{{ route('dashboard.index') }}" class="btn btn-outline-primary me-3">
                        <i class="fas fa-arrow-left me-2"></i>Орқага
                    </a>
                    <div>
                        <h1 class="display-6 fw-bold text-blue mb-0">
                            <i class="fas fa-file-contract me-2"></i>Шартномалар рўйхати
                        </h1>
                        <p class="text-muted mb-0">Жами: {{ $filterSummary['total_count'] }} та шартнома</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Filters Display -->
    @if($filterSummary['district'] || $filterSummary['status'] || $filterSummary['quarter'] || $filterSummary['year'])
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-blue">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <strong class="text-blue me-2"><i class="fas fa-filter me-1"></i>Фаол филтрлар:</strong>

                        @if($filterSummary['district'])
                        <span class="badge bg-blue text-white">
                            <i class="fas fa-map-marker-alt me-1"></i>{{ $filterSummary['district'] }}
                        </span>
                        @endif

                        @if($filterSummary['status'])
                        <span class="badge bg-blue text-white">
                            <i class="fas fa-info-circle me-1"></i>
                            @switch($filterSummary['status'])
                                @case('active') Амалдаги @break
                                @case('cancelled') Бекор қилинган @break
                                @case('completed') Тўлиқ тўланган @break
                            @endswitch
                        </span>
                        @endif

                        @if($filterSummary['quarter'])
                        <span class="badge bg-blue text-white">
                            <i class="fas fa-calendar-alt me-1"></i>{{ $filterSummary['quarter'] }} чорак
                            ({{ ucfirst($filterSummary['type']) }})
                        </span>
                        @endif

                        @if($filterSummary['year'])
                        <span class="badge bg-blue text-white">
                            <i class="fas fa-calendar me-1"></i>{{ $filterSummary['year'] }} йил
                            ({{ ucfirst($filterSummary['type']) }})
                        </span>
                        @endif

                        <a href="{{ route('contracts.index') }}" class="btn btn-sm btn-outline-danger ms-auto">
                            <i class="fas fa-times me-1"></i>Тозалаш
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Summary Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stats-card card-blue">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="flex-grow-1">
                            <div class="card-title">Тўланган</div>
                            <div class="card-value text-blue">{{ number_format($filterSummary['total_paid'] / 1000000000, 2) }}</div>
                            <div class="card-subtitle mt-2">млрд сўм</div>
                        </div>
                        <div class="icon-container">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stats-card card-blue">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="flex-grow-1">
                            <div class="card-title">Жами сумма</div>
                            <div class="card-value text-blue">{{ number_format($filterSummary['total_amount'] / 1000000000, 2) }}</div>
                            <div class="card-subtitle mt-2">млрд сўм</div>
                        </div>
                        <div class="icon-container">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stats-card card-blue">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="flex-grow-1">
                            <div class="card-title">Қарз</div>
                            <div class="card-value text-red">{{ number_format($filterSummary['total_debt'] / 1000000000, 2) }}</div>
                            <div class="card-subtitle mt-2">млрд сўм</div>
                        </div>
                        <div class="icon-container">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contracts Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-blue text-white">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Шартномалар жадвали</h5>
                </div>
                <div class="card-body p-0">
                    @if($contracts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>№</th>
                                    <th>Шартнома рақами</th>
                                    <th>Корхона</th>
                                    <th>Ҳудуд</th>
                                    <th>Шартнома суммаси</th>
                                    <th>Тўланган</th>
                                    <th>Қарз</th>
                                    <th>Ҳолат</th>
                                    <th>Сана</th>
                                    <th>Амаллар</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contracts as $contract)
                                @php
                                    $totalPaid = $contract->payments->sum('amount_debit') ?? 0;
                                    $debt = $contract->contract_amount - $totalPaid;
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration + ($contracts->currentPage() - 1) * $contracts->perPage() }}</td>
                                    <td>
                                        <span class="badge badge-blue">{{ $contract->contract_number }}</span>
                                    </td>
                                    <td class="text-start">{{ $contract->company_name }}</td>
                                    <td>
                                        <i class="fas fa-map-marker-alt text-blue me-1"></i>{{ $contract->district }}
                                    </td>
                                    <td class="text-end fw-bold">{{ number_format($contract->contract_amount / 100, 0, '.', '') }} сўм</td>
                                    <td class="text-end text-blue fw-bold">{{ number_format($totalPaid / 100, 0, '.', '') }} сўм</td>
                                    <td class="text-end text-red fw-bold">{{ number_format($debt / 100, 0, '.', '') }} сўм</td>
                                    <td>
                                        @if($contract->status === config('dashboard.statuses.active'))
                                            <span class="badge bg-blue text-white">
                                                <i class="fas fa-check-circle me-1"></i>Амалдаги
                                            </span>
                                        @elseif($contract->status === config('dashboard.statuses.completed'))
                                            <span class="badge bg-blue text-white">
                                                <i class="fas fa-check-double me-1"></i>Якунланган
                                            </span>
                                        @elseif($contract->status === config('dashboard.statuses.cancelled'))
                                            <span class="badge bg-danger text-white">
                                                <i class="fas fa-ban me-1"></i>Бекор қилинган
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">{{ $contract->status }}</span>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($contract->contract_date)->format('d.m.Y') }}</td>
                                    <td>
                                        <a href="{{ route('contracts.show', $contract->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted">
                                Кўрсатилмоқда: {{ $contracts->firstItem() ?? 0 }} - {{ $contracts->lastItem() ?? 0 }} / {{ $contracts->total() }}
                            </div>
                            {{ $contracts->links() }}
                        </div>
                    </div>
                    @else
                    <div class="p-5 text-center">
                        <i class="fas fa-inbox text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 text-muted">Шартномалар топилмади</h4>
                        <p class="text-muted">Филтрларни ўзгартириб қайта уриниб кўринг</p>
                        <a href="{{ route('contracts.index') }}" class="btn btn-primary mt-2">
                            <i class="fas fa-redo me-2"></i>Барча шартномаларни кўриш
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
