<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReplenishmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPerformanceController;
use App\Http\Controllers\UserController;
use App\Models\Supplier;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

// Supplier routes must resolve soft-deleted records too (restore action).
Route::bind('supplier', fn (string $value) => Supplier::withTrashed()->findOrFail($value));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.store');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
     * Role limits:
     *  - admin   : everything, including user accounts and report exports
     *  - manager : catalog, stock, transactions and order fulfilment
     *  - staff   : view everything and draft/edit orders, but no stock changes
     *  - user    : read-only view of inventory and reports
     */

    // Admin only: user accounts and roles.
    Route::middleware('can:manage-users')->group(function () {
        Route::patch('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
        Route::resource('users', UserController::class)->except(['show']);
    });

    // Admin + manager: catalog and stock movements.
    Route::middleware('can:manage-inventory')->group(function () {
        Route::patch('categories/{category}/restore', [CategoryController::class, 'restore'])->name('categories.restore');
        Route::resource('categories', CategoryController::class)->except(['index', 'show']);

        Route::patch('brands/{brand}/restore', [BrandController::class, 'restore'])->name('brands.restore');
        Route::resource('brands', BrandController::class)->except(['index', 'show']);

        Route::patch('products/{product}/restore', [ProductController::class, 'restore'])->name('products.restore');
        Route::resource('products', ProductController::class)->except(['index', 'show']);

        Route::get('transactions/adjust', [InventoryTransactionController::class, 'createAdjustment'])
            ->name('transactions.adjust');
        Route::post('transactions/adjust', [InventoryTransactionController::class, 'storeAdjustment'])
            ->name('transactions.adjust.store');
        Route::get('transactions/create', [InventoryTransactionController::class, 'create'])
            ->name('transactions.create');
        Route::post('transactions', [InventoryTransactionController::class, 'store'])
            ->name('transactions.store');
        Route::delete('transactions/{transaction}', [InventoryTransactionController::class, 'destroy'])
            ->middleware('can:admin')
            ->name('transactions.destroy');
    });

    // Read-only catalog and stock pages for every signed-in user.
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::resource('categories', CategoryController::class)->only(['index']);
    Route::resource('brands', BrandController::class)->only(['index']);
    Route::resource('products', ProductController::class)->only(['index', 'show']);
    Route::get('transactions', [InventoryTransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/{transaction}', [InventoryTransactionController::class, 'show'])->name('transactions.show');

    // Lab 5 activity log (audit trail) for sign-ins and inventory changes.
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])
        ->middleware('can:export-reports')
        ->name('reports.export');

    // Sales receipts: read-only list of completed orders for every signed-in user.
    Route::get('receipts', [SalesOrderController::class, 'receipts'])->name('receipts.index');

    /*
     * Lab 7: Supplier management.
     * Read access is available to every authenticated user; writes use the
     * SupplierPolicy (admin + manager), deletion and restore are admin-only.
     */
    Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
    Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('supplier-performance', [SupplierPerformanceController::class, 'index'])->name('supplier-performance.index');
    Route::get('supplier-performance/export', [SupplierPerformanceController::class, 'export'])
        ->name('supplier-performance.export');
    Route::patch('suppliers/{supplier}/restore', [SupplierController::class, 'restore'])->name('suppliers.restore');
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::get('suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
    Route::patch('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

    // Purchase orders: draft -> pending -> approved -> sent -> received.
    Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
    Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
    Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    Route::get('purchase-orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
    Route::patch('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
    Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');
    Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::post('purchase-orders/{purchaseOrder}/send', [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');

    // Replenishment exports and manual command trigger (admin / manager).
    Route::middleware('can:manage-inventory')->group(function () {
        Route::get('exports/products', [ExportController::class, 'products'])->name('exports.products');
        Route::get('exports/purchase-orders', [ExportController::class, 'purchaseOrders'])->name('exports.purchase-orders');
        Route::get('exports/transactions', [ExportController::class, 'transactions'])->name('exports.transactions');
        Route::get('exports/{type}.pdf', [ExportController::class, 'pdf'])->name('exports.pdf');
        Route::post('replenishment/run', [ReplenishmentController::class, 'run'])->name('replenishment.run');
    });

    // Sales orders: staff may draft, admin/manager may complete and cancel.
    Route::get('sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::middleware('can:create-orders')->group(function () {
        Route::get('sales-orders/create', [SalesOrderController::class, 'create'])->name('sales-orders.create');
        Route::post('sales-orders', [SalesOrderController::class, 'store'])->name('sales-orders.store');
        Route::get('sales-orders/{salesOrder}/edit', [SalesOrderController::class, 'edit'])->name('sales-orders.edit');
        Route::patch('sales-orders/{salesOrder}', [SalesOrderController::class, 'update'])->name('sales-orders.update');
    });
    Route::get('sales-orders/{salesOrder}', [SalesOrderController::class, 'show'])->name('sales-orders.show');
    Route::get('sales-orders/{salesOrder}/receipt', [SalesOrderController::class, 'receipt'])->name('sales-orders.receipt');
    Route::post('sales-orders/{salesOrder}/approve', [SalesOrderController::class, 'approve'])
        ->middleware('can:admin')
        ->name('sales-orders.approve');
    Route::middleware('can:fulfil-orders')->group(function () {
        Route::delete('sales-orders/{salesOrder}', [SalesOrderController::class, 'destroy'])->name('sales-orders.destroy');
        Route::post('sales-orders/{salesOrder}/complete', [SalesOrderController::class, 'complete'])->name('sales-orders.complete');
        Route::post('sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel'])->name('sales-orders.cancel');
    });
});

/*
 * Malformed URLs such as /products//////.......////// should not dead-end at a
 * 404. Send them back to the page the user was on instead. External referrers,
 * another malformed URL, and the fallback target itself are rejected to avoid
 * redirect loops.
 */
Route::fallback(function () {
    $safeTarget = route('dashboard');
    $previous = request()->header('referer');
    $base = rtrim(url('/'), '/');

    if (! is_string($previous) || $previous === '' || $previous === url()->full()) {
        return redirect()->to($safeTarget);
    }

    if ($previous !== $base && ! str_starts_with($previous, $base.'/')) {
        return redirect()->to($safeTarget);
    }

    $path = parse_url($previous, PHP_URL_PATH) ?: '';

    if (preg_match('#/{2,}|\\.{2,}#', $path)) {
        return redirect()->to($safeTarget);
    }

    return redirect()->to($previous);
});
