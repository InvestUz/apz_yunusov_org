<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ContractController;

use App\Models\Contract;
use Illuminate\Support\Facades\Log;

Route::get('/debug-core-status-contracts', function () {
    $active = config('dashboard.statuses.active');       // амал қилувчи
    $completed = config('dashboard.statuses.completed'); // якунланган

    $contracts = Contract::whereIn('status', [$active, $completed])->get();

    Log::info('Core status contracts summary', [
        'active_label'    => $active,
        'completed_label' => $completed,
        'total'           => $contracts->count(),
        'by_status'       => $contracts->groupBy('status')->map->count(),
        'total_amount'    => (float) $contracts->sum('contract_amount'),
    ]);

    return 'Logged. Check storage/logs/laravel.log';
});
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/dashboard');
});

// ART Monitoring Dashboard Routes
Route::prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart-data');
});

// Contracts Routes
Route::resource('contracts', ContractController::class);

// API Routes for Dashboard
Route::prefix('api/dashboard')->group(function () {
    Route::get('/summary', [DashboardController::class, 'summary']);
    Route::get('/contracts', [DashboardController::class, 'contracts']);
    Route::get('/debts', [DashboardController::class, 'debts']);
    Route::get('/overdue', [DashboardController::class, 'overdue']);
});
