<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

test('cashier role can access POS terminal', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole(Role::firstOrCreate(['name' => 'cashier']));

    $this->actingAs($cashier)
        ->get(route('pos'))
        ->assertOk();
});

test('cashier role cannot access inventory', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole(Role::firstOrCreate(['name' => 'cashier']));

    $this->actingAs($cashier)
        ->get(route('inventory.products'))
        ->assertForbidden();
});

test('manager role can access inventory', function () {
    $manager = User::factory()->create();
    $manager->assignRole(Role::firstOrCreate(['name' => 'manager']));

    $this->actingAs($manager)
        ->get(route('inventory.products'))
        ->assertOk();
});

test('manager role can access deliveries', function () {
    $manager = User::factory()->create();
    $manager->assignRole(Role::firstOrCreate(['name' => 'manager']));

    $this->actingAs($manager)
        ->get(route('deliveries.index'))
        ->assertOk();
});

test('admin role can access all routes', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::firstOrCreate(['name' => 'admin']));

    $this->actingAs($admin)->get(route('pos'))->assertOk();
    $this->actingAs($admin)->get(route('inventory.products'))->assertOk();
    $this->actingAs($admin)->get(route('deliveries.index'))->assertOk();
});

test('unauthenticated user is redirected from POS', function () {
    $this->get(route('pos'))->assertRedirect(route('login'));
});

test('user has correct permissions via role', function () {
    $cashier = User::factory()->create();
    $role    = Role::firstOrCreate(['name' => 'cashier']);
    $role->syncPermissions([
        Permission::firstOrCreate(['name' => 'pos.access']),
        Permission::firstOrCreate(['name' => 'dashboard.view']),
    ]);
    $cashier->assignRole($role);

    // Create inventory.manage so hasPermissionTo() returns false instead of throwing
    Permission::firstOrCreate(['name' => 'inventory.manage']);

    expect($cashier->hasPermissionTo('pos.access'))->toBeTrue();
    expect($cashier->hasPermissionTo('inventory.manage'))->toBeFalse();
});

