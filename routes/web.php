<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SalesActivityController;
use App\Http\Controllers\LeadsController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\TaskReminderController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ArtisanController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DeletionRequestController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ProductController;

// ── Artisan runner (shared hosting) ──
Route::get('/run/{command}', [ArtisanController::class, 'run'])
    ->name('artisan.run')
    ->middleware(['auth', 'role:Admin,Sales Manager', 'throttle:10,1']);

// ── Auth ──────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// ── Auth required ──────────────────────────────────
Route::middleware(['auth', 'direct-delete'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/search',    [SearchController::class, 'search'])->name('search');

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',               [NotificationController::class, 'index'])->name('index');
        Route::get('/unread-count',   [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('mark-all-read');
        Route::post('/{notification}/read', [NotificationController::class, 'markRead'])->name('mark-read');
    });

    // Semua role dapat mengajukan; Administrator/Developer dapat meninjau.
    Route::post('/deletion-requests', [DeletionRequestController::class, 'store'])
        ->name('deletion-requests.store');
    Route::middleware('role:Admin')->group(function () {
        Route::get('/deletion-requests', [DeletionRequestController::class, 'index'])
            ->name('deletion-requests.index');
        Route::post('/deletion-requests/{deletionRequest}/approve', [DeletionRequestController::class, 'approve'])
            ->name('deletion-requests.approve');
        Route::post('/deletion-requests/{deletionRequest}/reject', [DeletionRequestController::class, 'reject'])
            ->name('deletion-requests.reject');
    });

    // Sales Activity
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/activity',  [SalesActivityController::class, 'index'])->name('activity');
        Route::post('/activity', [SalesActivityController::class, 'store'])->name('activity.store');
    });

    // Leads & Pipeline
    Route::get('/leads/export',           [LeadsController::class, 'export'])->name('leads.export');
    Route::get('/leads/template',         [LeadsController::class, 'template'])->name('leads.template');
    Route::post('/leads/import',          [LeadsController::class, 'import'])->name('leads.import');
    Route::post('/leads/{lead}/activity', [LeadsController::class, 'storeActivity'])->name('leads.activity.store');
    Route::post('/leads/{lead}/products', [LeadsController::class, 'storeProduct'])->name('leads.products.store');
    Route::delete('/leads/{lead}/products/{product}', [LeadsController::class, 'destroyProduct'])->name('leads.products.destroy');
    Route::post('/leads/{lead}/pics',     [LeadsController::class, 'storePic'])->name('leads.pics.store');
    Route::delete('/leads/{lead}/pics/{pic}', [LeadsController::class, 'destroyPic'])->name('leads.pics.destroy');
    Route::resource('leads', LeadsController::class);
    Route::get('/pipeline', [PipelineController::class, 'index'])->name('pipeline.index');

    // Calendar & Tasks
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/tasks',               [TaskReminderController::class, 'index'])->name('tasks.index');
    Route::post('/tasks',              [TaskReminderController::class, 'store'])->name('tasks.store');
    Route::patch('/tasks/{activity}',  [TaskReminderController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{activity}', [TaskReminderController::class, 'destroy'])->name('tasks.destroy');

    // CRM Data
    Route::get('/customers/export',               [CustomerController::class, 'export'])->name('customers.export');
    Route::get('/customers/template',             [CustomerController::class, 'template'])->name('customers.template');
    Route::post('/customers/import',              [CustomerController::class, 'import'])->name('customers.import');
    Route::post('/customers/{customer}/activity', [CustomerController::class, 'storeActivity'])->name('customers.activity.store');
    Route::post('/customers/{customer}/pics',     [CustomerController::class, 'storePic'])->name('customers.pics.store');
    Route::delete('/customers/{customer}/pics/{pic}', [CustomerController::class, 'destroyPic'])->name('customers.pics.destroy');
    Route::patch('/customers/{customer}/transfer-sales', [CustomerController::class, 'transferSales'])->name('customers.transfer-sales');
    Route::resource('customers', CustomerController::class);

    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
    Route::resource('products', ProductController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);

    // Sales documents
    Route::get('/invoices/export', [InvoiceController::class, 'export'])->name('invoices.export');
    Route::get('/invoices/{id}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::get('/invoices/{id}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::resource('invoices', InvoiceController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['invoices' => 'id']);

    Route::get('/quotations/export', [QuotationController::class, 'export'])->name('quotations.export');
    Route::get('/quotations/{id}/print', [QuotationController::class, 'print'])->name('quotations.print');
    Route::get('/quotations/{id}/edit', [QuotationController::class, 'edit'])->name('quotations.edit');
    Route::resource('quotations', QuotationController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['quotations' => 'id']);

    // Suppliers & Purchase Orders (Admin & Sales Manager only)
    Route::middleware('role:Admin,Sales Manager')->group(function () {
        Route::get('/suppliers/export', [SupplierController::class, 'export'])->name('suppliers.export');
        Route::post('/suppliers/{supplier}/products', [SupplierController::class, 'storeProduct'])->name('suppliers.products.store');
        Route::delete('/suppliers/{supplier}/products/{product}', [SupplierController::class, 'destroyProduct'])->name('suppliers.products.destroy');
        Route::post('/suppliers/{supplier}/pics', [SupplierController::class, 'storePic'])->name('suppliers.pics.store');
        Route::delete('/suppliers/{supplier}/pics/{pic}', [SupplierController::class, 'destroyPic'])->name('suppliers.pics.destroy');
        Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('/purchase-orders/export', [PurchaseOrderController::class, 'export'])->name('purchase-orders.export');
        Route::get('/purchase-orders/{purchaseOrder}/print', [PurchaseOrderController::class, 'print'])->name('purchase-orders.print');
        Route::get('/purchase-orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
        Route::resource('purchase-orders', PurchaseOrderController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    // ── Manager & Admin only ───────────────────────
    Route::middleware('role:Admin,Sales Manager')->group(function () {
        Route::get('/analytics',      [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/reports',        [ReportsController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportsController::class, 'export'])->name('reports.export');
    });

    // ── Admin only ─────────────────────────────────
    Route::middleware('role:Admin')->group(function () {
        Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/delete-image', [SettingsController::class, 'deleteLogo'])->name('settings.delete-image');
    });
});
