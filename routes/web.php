<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ContractController;

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
