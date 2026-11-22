<?php

use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

// The dashboard polls these endpoints with the logged-in web session, so the
// web middleware stack (sessions + CSRF cookie) must be applied explicitly.
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('api.dashboard.stats');
    Route::get('/products/low-stock', [DashboardController::class, 'lowStock'])->name('api.products.low-stock');
    Route::get('/transactions/recent', [DashboardController::class, 'recentTransactions'])->name('api.transactions.recent');

    // Lab 7 procurement widget endpoints (polled by the dashboard).
    Route::get('/dashboard/pending-orders', [DashboardController::class, 'pendingOrders'])->name('api.dashboard.pending-orders');
    Route::get('/dashboard/recent-orders', [DashboardController::class, 'recentOrders'])->name('api.dashboard.recent-orders');
    Route::get('/dashboard/supplier-performance', [DashboardController::class, 'supplierPerformance'])->name('api.dashboard.supplier-performance');
    Route::get('/dashboard/reorder-alerts', [DashboardController::class, 'reorderAlerts'])->name('api.dashboard.reorder-alerts');
});
