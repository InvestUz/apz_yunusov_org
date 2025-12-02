<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ART Monitoring')</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --blue-primary: #2563eb;
            --blue-secondary: #3b82f6;
            --blue-light: #dbeafe;
            --red-primary: #dc2626;
            --red-secondary: #ef4444;
            --red-light: #fee2e2;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-700: #374151;
            --gray-900: #111827;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--gray-50);
            font-size: 15px;
            line-height: 1.6;
        }

        .navbar {
            background: linear-gradient(135deg, var(--blue-primary) 0%, #1e40af 100%);
        }

        /* Statistics Card Styles - Matching Image Design */
        .stats-card {
            border: 3px solid;
            border-radius: 16px;
            background: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            padding: 2rem !important;
            min-height: 220px;
            display: flex;
            align-items: center;
        }

        .stats-card.card-blue {
            border-color: var(--blue-primary);
        }

        .stats-card.card-red {
            border-color: var(--red-primary);
        }

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }

        .stats-card .icon-container {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .stats-card.card-blue .icon-container {
            background-color: var(--blue-light);
            color: var(--blue-primary);
        }

        .stats-card.card-red .icon-container {
            background-color: var(--red-light);
            color: var(--red-primary);
        }

        .stats-card .card-title {
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-700);
            margin-bottom: 14px;
        }

        .stats-card .card-value {
            font-size: 3.25rem;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 8px;
        }

        .stats-card.card-blue .card-value {
            color: var(--blue-primary);
        }

        .stats-card.card-red .card-value {
            color: var(--red-primary);
        }

        .stats-card .card-subtitle {
            font-size: 1.05rem;
            color: var(--gray-700);
            margin-top: 8px;
            font-weight: 500;
        }

        /* Progress Bar Styles */
        .progress-bar-custom {
            height: 8px;
            background-color: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 12px;
        }

        .progress-bar-custom .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .progress-bar-custom .progress-fill.red {
            background: linear-gradient(90deg, var(--red-primary) 0%, var(--red-secondary) 100%);
        }

        /* Table Styles */
        .card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .card-header {
            border-radius: 16px 16px 0 0 !important;
            padding: 1.25rem 1.5rem;
            border: none;
        }

        .card-header h5 {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .table-responsive {
            border-radius: 0 0 16px 16px;
            overflow: hidden;
        }

        .table thead {
            background: var(--blue-primary);
            color: white;
        }

        .text-blue { color: var(--blue-primary) !important; }
        .text-red { color: var(--red-primary) !important; }
        .bg-blue { background-color: var(--blue-primary) !important; }
        .bg-red { background-color: var(--red-primary) !important; }

        .monitoring-table {
            font-size: 0.95rem;
            margin-bottom: 0;
        }

        .monitoring-table th,
        .monitoring-table td {
            padding: 0.875rem 0.75rem;
            text-align: center;
            vertical-align: middle;
        }

        .monitoring-table thead th {
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .monitoring-table tbody td {
            font-size: 0.95rem;
        }

        .monitoring-table .district-col {
            text-align: left;
            font-weight: 700;
            font-size: 1rem;
        }

        .monitoring-table td.clickable {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .monitoring-table td.clickable:hover {
            background-color: var(--blue-light);
            color: var(--blue-primary);
            font-weight: 600;
            transform: scale(1.05);
        }

        .monitoring-table .total-row {
            background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%) !important;
            border-top: 3px solid var(--blue-primary);
            border-bottom: 3px solid var(--blue-primary);
            font-weight: 700;
            font-size: 1.05rem;
        }

        .monitoring-table .total-row td {
            padding: 1rem 0.75rem !important;
        }

        .amount-billion {
            font-weight: 700;
            font-size: 0.95rem;
        }

        /* Badge Styles */
        .badge-blue {
            background-color: var(--blue-light);
            color: var(--blue-primary);
            border: 1px solid var(--blue-primary);
            font-weight: 600;
            padding: 0.5rem 0.875rem;
        }

        .badge-red {
            background-color: var(--red-light);
            color: var(--red-primary);
            border: 1px solid var(--red-primary);
            font-weight: 600;
            padding: 0.5rem 0.875rem;
        }

        /* Scrollbar Styling */
        .table-responsive::-webkit-scrollbar {
            height: 10px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 5px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: var(--blue-primary);
            border-radius: 5px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: var(--blue-secondary);
        }

        /* 5-column grid for stats cards */
        @media (min-width: 1200px) {
            .col-xl-20p {
                flex: 0 0 auto;
                width: 20%;
            }
        }
    </style>

    @yield('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('dashboard.index') }}">
                <i class="fas fa-chart-line"></i> ART MONITORING
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard.index') }}">
                            <i class="fas fa-home"></i> Асосий
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        @yield('content')
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @yield('scripts')
</body>
</html>
