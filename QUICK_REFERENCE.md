# Quick Reference: Optimized Code Usage

## How to Use the Optimized Dashboard

### 1. Get Dashboard Statistics

```php
use App\Services\DashboardService;

$dashboardService = app(DashboardService::class);

// Get all stats
$stats = $dashboardService->getDashboardStats();

// Access data (type-safe)
echo $stats->totalContracts;      // int
echo $stats->activeContracts;     // int
echo $stats->totalAmount;         // float
echo $stats->totalPaid;           // float
echo $stats->totalDebt;           // float

// With period filter
$stats = $dashboardService->getDashboardStats('month');
```

### 2. Get District Statistics

```php
$districtStats = $dashboardService->getDistrictStats();

foreach ($districtStats as $district) {
    echo $district->districtName;      // string
    echo $district->contractsCount;    // int
    echo $district->totalAmount;       // float
    echo $district->paidToday;         // float
    echo $district->paidWeek;          // float
    echo $district->paidMonth;         // float
    echo $district->paidQuarter;       // float
}
```

### 3. Get Chart Data

```php
// For monthly chart
$chartData = $dashboardService->getChartData('month');

// For quarterly chart
$chartData = $dashboardService->getChartData('quarter');

// For yearly chart
$chartData = $dashboardService->getChartData('year');
```

### 4. Get Recent Data

```php
// Get recent contracts (default: 5)
$recentContracts = $dashboardService->getRecentContracts();

// Get recent payments (default: 5)
$recentPayments = $dashboardService->getRecentPayments();

// Custom limit
$recentContracts = $dashboardService->getRecentContracts(10);
```

### 5. Get Status Distribution

```php
$statusDistribution = $dashboardService->getStatusDistribution();

// Returns:
// [
//     ['name_uz' => 'Амал қилувчи', 'code' => 'ACTIVE', 'color' => '#28a745', 'count' => 29],
//     ['name_uz' => 'Бекор қилинган', 'code' => 'CANCELLED', 'color' => '#dc3545', 'count' => 1],
//     ['name_uz' => 'Якунланган', 'code' => 'COMPLETED', 'color' => '#007bff', 'count' => 23],
// ]
```

## Using Repositories Directly

### Contract Repository

```php
use App\Repositories\ContractRepository;

$repo = app(ContractRepository::class);

$totalCount = $repo->getTotalCount();
$activeContracts = $repo->getActive();
$byDistrict = $repo->getByDistrict();
$legalEntities = $repo->getLegalEntitiesCount();
```

### Payment Repository

```php
use App\Repositories\PaymentRepository;

$repo = app(PaymentRepository::class);

$totalPaid = $repo->getTotalPaid();
$paidToday = $repo->getTotalPaidByPeriod('today');
$paidMonth = $repo->getTotalPaidByPeriod('month');
$recent = $repo->getRecentPayments(10);
```

### Payment Schedule Repository

```php
use App\Repositories\PaymentScheduleRepository;

$repo = app(PaymentScheduleRepository::class);

$schedules = $repo->getGroupedByPeriod();
$overdueDebt = $repo->getTotalOverdueDebt();
$overdueSchedules = $repo->getOverdueSchedules();
```

## Configuration

### Modify Status Values

Edit `config/dashboard.php`:

```php
'statuses' => [
    'active' => 'Амал қилувчи',
    'cancelled' => 'Бекор қилинган',
    'completed' => 'Якунланган',
],
```

### Modify Display Settings

```php
'formatting' => [
    'currency_symbol' => 'сўм',
    'billion_divisor' => 1000000000,
    'million_divisor' => 1000000,
    'billion_suffix' => 'млрд',
    'million_suffix' => 'млн',
    'decimal_places' => 2,
],
```

### Change Dashboard Limits

```php
'limits' => [
    'recent_contracts' => 5,    // Change to 10, 20, etc.
    'recent_payments' => 5,
    'top_districts' => 10,
],
```

## API Endpoints

### GET /dashboard
Returns dashboard view or JSON (based on Accept header)

**Query Parameters:**
- `period_filter`: today, week, month, quarter, year, all

**Example:**
```
GET /dashboard?period_filter=month
```

### GET /dashboard/chart-data
Returns chart data

**Query Parameters:**
- `period`: month, quarter, year

**Example:**
```
GET /dashboard/chart-data?period=month
```

**Response:**
```json
[
    {"label": "Jan 2025", "actual": 16703.45, "planned": 0},
    {"label": "Feb 2025", "actual": 7592.41, "planned": 4720.93}
]
```

### GET /api/dashboard/summary
Returns dashboard statistics

**Response:**
```json
{
    "total_contracts": 53,
    "total_amount": 20125330000000,
    "active_contracts": 29,
    "active_amount": 15877330000000,
    "total_paid": 40289460000000,
    "total_debt": 11962230000000
}
```

### GET /api/dashboard/contracts
Returns district statistics

**Response:**
```json
[
    {
        "district_name": "Яшнобод",
        "contracts_count": 9,
        "total_amount": 5234000000,
        "paid_today": 0,
        "paid_week": 120000000,
        "paid_month": 450000000
    }
]
```

## Controller Usage

```php
namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}
    
    public function index(Request $request)
    {
        $period = $request->get('period_filter', 'all');
        $stats = $this->dashboardService->getDashboardStats($period);
        
        return view('dashboard.index', compact('stats'));
    }
}
```

## Testing Example

```php
use Tests\TestCase;
use App\Services\DashboardService;
use App\Repositories\ContractRepository;
use Mockery;

class DashboardServiceTest extends TestCase
{
    public function test_get_dashboard_stats()
    {
        // Mock repository
        $mockRepo = Mockery::mock(ContractRepository::class);
        $mockRepo->shouldReceive('getTotalCount')->andReturn(100);
        $mockRepo->shouldReceive('getActiveCount')->andReturn(50);
        
        // Bind mock
        $this->app->instance(ContractRepository::class, $mockRepo);
        
        // Test service
        $service = app(DashboardService::class);
        $stats = $service->getDashboardStats();
        
        $this->assertEquals(100, $stats->totalContracts);
        $this->assertEquals(50, $stats->activeContracts);
    }
}
```

## Common Tasks

### Add a New Status

1. Add to config:
```php
// config/dashboard.php
'statuses' => [
    'active' => 'Амал қилувчи',
    'pending' => 'Кутилмоқда',  // New status
],
```

2. Add scope to model:
```php
// app/Models/Contract.php
public function scopePending($query)
{
    return $query->where('status', config('dashboard.statuses.pending'));
}
```

3. Add repository method:
```php
// app/Repositories/ContractRepository.php
public function getPendingCount(): int
{
    return Contract::pending()->count();
}
```

### Add Period Filter

Just use existing method:
```php
$stats = $dashboardService->getDashboardStats('week');
// Supports: today, week, month, quarter, year, all
```

### Format Amount

```php
// Using service method
$formatted = $dashboardService->formatAmount(1234567890000);
// Output: "1,234.57 млрд"

// Or use config directly
$amount = 1234567890;
$divisor = config('dashboard.formatting.million_divisor');
$suffix = config('dashboard.formatting.million_suffix');
echo number_format($amount / $divisor, 2) . ' ' . $suffix;
// Output: "1,234.57 млн"
```

## Benefits Checklist

- ✅ No hard-coded values
- ✅ No CSV files in routes
- ✅ Database-driven data
- ✅ Type-safe DTOs
- ✅ Repository pattern
- ✅ Dependency injection
- ✅ Config-based constants
- ✅ SOLID principles
- ✅ DRY code
- ✅ Testable architecture
- ✅ Clean routes (32 lines vs 271)
- ✅ Maintainable codebase
