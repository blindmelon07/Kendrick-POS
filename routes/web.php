<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

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

    // User Management — Admin only
    Route::middleware('role:admin')->group(function () {
        Route::livewire('users', 'users.management')->name('users.index');
        Route::livewire('admin/backup', 'admin.backup')->name('admin.backup');
    });
});

require __DIR__.'/settings.php';
