<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ART Monitoring</title>
  <!-- Bootstrap CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
    .number { white-space: nowrap; }
    .badge-green { background-color: #28a745; }
    .badge-red { background-color: #dc3545; }
    .badge-blue { background-color: #0d6efd; }
    .badge-orange { background-color: #fd7e14; }
    .table thead th { position: sticky; top: 0; background: #fff; z-index: 1; }
  </style>
</head>
<body class="bg-light">
<div class="container-fluid py-4">
  <div class="d-flex align-items-center mb-3">
    <h2 class="me-3">ART Monitoring</h2>
    <span class="badge badge-blue">Contracts</span>
    <span class="badge badge-green ms-2">Plan</span>
    <span class="badge badge-orange ms-2">Fact</span>
    <span class="badge badge-red ms-2">Debt</span>
  </div>

  <!-- Totals summary -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="fw-bold">Total contracts</div>
          <div class="display-6">{{ number_format($totals['contracts_count']) }}</div>
          <div class="text-muted">Sum: <span class="number">{{ number_format($totals['contracts_sum']/1_000_000_000, 2) }} млрд</span></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="fw-bold">Active</div>
          <div class="display-6">{{ number_format($totals['active_count']) }}</div>
          <div class="text-muted">Sum: <span class="number">{{ number_format($totals['active_sum']/1_000_000_000, 2) }} млрд</span></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="fw-bold">Canceled</div>
          <div class="display-6">{{ number_format($totals['cancel_count']) }}</div>
          <div class="text-muted">Sum: <span class="number">{{ number_format($totals['cancel_sum']/1_000_000_000, 2) }} млрд</span></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="fw-bold">Closed</div>
          <div class="display-6">{{ number_format($totals['closed_count']) }}</div>
          <div class="text-muted">Sum: <span class="number">{{ number_format($totals['closed_sum']/1_000_000_000, 2) }} млрд</span></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Chart: 2025 Q3 Plan vs Fact by region -->
  <div class="card mb-4 shadow-sm">
    <div class="card-body">
      <h5 class="card-title mb-3">2025 Q3: Plan vs Fact by region</h5>
      <canvas id="chartQ3"></canvas>
    </div>
  </div>

  <!-- Region table -->
  <div class="card shadow-sm">
    <div class="card-body">
      <h5 class="card-title mb-3">Per-region metrics</h5>
      <div class="table-responsive" style="max-height: 65vh;">
        <table class="table table-sm table-bordered align-middle">
          <thead class="table-light">
          <tr>
            <th>Ҳудуд</th>
            <th>Жами шартномалар (сони)</th>
            <th>Жами шартномалар (млрд)</th>
            <th>Амалдаги (сони)</th>
            <th>Амалдаги (млрд)</th>
            <th>Бекор қилинган (сони)</th>
            <th>Бекор қилинган (млрд)</th>
            <th>Тўлиқ тўланган (сони)</th>
            <th>Тўлиқ тўланган (млрд)</th>
            <th>2025 III чорак Режа (млрд)</th>
            <th>2025 III чорак Факт (млрд)</th>
            <th>2025 III чорак Қарз (млрд)</th>
            <th>2025 Йил Режа (млрд)</th>
            <th>2025 Йил Факт (млрд)</th>
            <th>2025 Йил Қарз (млрд)</th>
            <th>2026 Йил Режа (млрд)</th>
            <th>2026 Йил Факт (млрд)</th>
            <th>2026 Йил Қарз (млрд)</th>
            <th>2027 Йил Режа (млрд)</th>
            <th>2027 Йил Факт (млрд)</th>
            <th>2027 Йил Қарз (млрд)</th>
          </tr>
          </thead>
          <tbody>
          @foreach($regions as $name => $r)
            <tr>
              <td class="fw-semibold">{{ $name }}</td>
              <td>{{ number_format($r['contracts_count']) }}</td>
              <td class="number">{{ number_format($r['contracts_sum']/1_000_000_000, 3) }}</td>
              <td>{{ number_format($r['active_count']) }}</td>
              <td class="number">{{ number_format($r['active_sum']/1_000_000_000, 3) }}</td>
              <td>{{ number_format($r['cancel_count']) }}</td>
              <td class="number">{{ number_format($r['cancel_sum']/1_000_000_000, 3) }}</td>
              <td>{{ number_format($r['closed_count']) }}</td>
              <td class="number">{{ number_format($r['closed_sum']/1_000_000_000, 3) }}</td>
              <td class="number">{{ number_format($r['q3_2025_plan']/1_000_000_000, 3) }}</td>
              <td class="number">{{ number_format($r['q3_2025_fact']/1_000_000_000, 3) }}</td>
              <td class="number">
                <span class="badge {{ $r['q3_2025_debt']>0 ? 'badge-red' : 'badge-green' }}">{{ number_format($r['q3_2025_debt']/1_000_000_000, 3) }}</span>
              </td>
              <td class="number">{{ number_format($r['y2025_plan']/1_000_000_000, 3) }}</td>
              <td class="number">{{ number_format($r['y2025_fact']/1_000_000_000, 3) }}</td>
              <td class="number">
                <span class="badge {{ $r['y2025_debt']>0 ? 'badge-red' : 'badge-green' }}">{{ number_format($r['y2025_debt']/1_000_000_000, 3) }}</span>
              </td>
              <td class="number">{{ number_format($r['y2026_plan']/1_000_000_000, 3) }}</td>
              <td class="number">{{ number_format($r['y2026_fact']/1_000_000_000, 3) }}</td>
              <td class="number">
                <span class="badge {{ $r['y2026_debt']>0 ? 'badge-red' : 'badge-green' }}">{{ number_format($r['y2026_debt']/1_000_000_000, 3) }}</span>
              </td>
              <td class="number">{{ number_format($r['y2027_plan']/1_000_000_000, 3) }}</td>
              <td class="number">{{ number_format($r['y2027_fact']/1_000_000_000, 3) }}</td>
              <td class="number">
                <span class="badge {{ $r['y2027_debt']>0 ? 'badge-red' : 'badge-green' }}">{{ number_format($r['y2027_debt']/1_000_000_000, 3) }}</span>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  const regions = @json(array_keys($regions));
  const planQ3 = @json(array_map(fn($r) => round($r['q3_2025_plan']/1_000_000_000,3), $regions));
  const factQ3 = @json(array_map(fn($r) => round($r['q3_2025_fact']/1_000_000_000,3), $regions));

  const ctx = document.getElementById('chartQ3');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: regions,
      datasets: [
        { label: 'Plan (млрд)', data: planQ3, backgroundColor: '#0d6efd' },
        { label: 'Fact (млрд)', data: factQ3, backgroundColor: '#fd7e14' },
      ]
    },
    options: {
      responsive: true,
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
</script>
</body>
</html>
