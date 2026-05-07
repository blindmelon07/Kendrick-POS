<?php

use Illuminate\Support\Facades\Route;

// Admin login alias
Route::redirect('administrator/login', '/login')->name('admin.login');

// PayMongo webhook (must be outside CSRF middleware — handled via web middleware exclusion)
Route::post('paymongo/webhook', \App\Http\Controllers\PayMongoWebhookController::class)
    ->name('paymongo.webhook');

// Payment return pages (after customer pays or cancels on PayMongo)
Route::middleware('auth')->group(function () {
    Route::livewire('payment/success/{orderId}', 'public.payment-result')->name('payment.success');
    Route::livewire('payment/cancel/{orderId}',  'public.payment-result')->name('payment.cancel');
});

// Customer-facing auth pages
Route::middleware('guest')->group(function () {
    Route::livewire('customer/login', 'auth.customer-login')->name('customer.login');
    Route::livewire('customer/register', 'auth.customer-register')->name('customer.register');
});

// Public-facing food ordering pages
Route::livewire('/', 'public.home')->name('home');
Route::livewire('/menu', 'public.menu')->name('public.menu');
Route::livewire('/cart', 'public.cart')->name('public.cart');

Route::middleware('auth')->group(function () {
    Route::livewire('/checkout', 'public.checkout')->name('public.checkout');
    Route::livewire('/my-orders', 'public.my-orders')->name('public.my-orders');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // POS — Cashier & Admin
    Route::middleware('role:cashier|admin')->group(function () {
        Route::livewire('pos', 'pos.terminal')->name('pos');
        Route::livewire('pos/history', 'pos.transaction-history')->name('pos.history');
    });

    // Inventory — Admin & Manager
    Route::middleware('role:manager|admin')->group(function () {
        Route::livewire('inventory/products', 'inventory.products')->name('inventory.products');
        Route::livewire('inventory/stock', 'inventory.stock-in')->name('inventory.stock');
        Route::livewire('inventory/log', 'inventory.adjustment-log')->name('inventory.log');
    });

    // Deliveries — Admin & Manager
    Route::middleware('role:manager|admin')->group(function () {
        Route::livewire('deliveries', 'deliveries.delivery-orders')->name('deliveries.index');
        Route::livewire('deliveries/create', 'deliveries.delivery-form')->name('deliveries.create');
        Route::livewire('suppliers', 'suppliers.management')->name('suppliers.index');
    });

    // Customer Orders & Customers — Admin & Manager
    Route::middleware('role:manager|admin')->group(function () {
        Route::livewire('orders', 'orders.orders')->name('orders.index');
        Route::livewire('orders/create', 'orders.order-form')->name('orders.create');
        Route::livewire('customers', 'customers.management')->name('customers.index');
        Route::livewire('daily-menu', 'daily-menu.management')->name('daily-menu.index');
    });

    // Employees — Admin & Manager
    Route::middleware('role:manager|admin')->group(function () {
        Route::livewire('employees', 'employees.employees')->name('employees.index');
        Route::livewire('employees/{employeeId}', 'employees.employee-profile')->name('employees.show');
    });

    // User Management — Admin only
    Route::middleware('role:admin')->group(function () {
        Route::livewire('users', 'users.management')->name('users.index');
        Route::livewire('admin/backup', 'admin.backup')->name('admin.backup');
    });
});

require __DIR__.'/settings.php';
