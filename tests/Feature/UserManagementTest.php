<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'manager']);
    Role::firstOrCreate(['name' => 'cashier']);
});

test('admin can access user management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk();
});

test('manager cannot access user management page', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)
        ->get(route('users.index'))
        ->assertForbidden();
});

test('admin can create a new user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    \Livewire\Livewire::test('users.management')
        ->set('name', 'New User')
        ->set('email', 'newuser@example.com')
        ->set('password', 'password123')
        ->set('selectedRole', 'cashier')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    expect(User::where('email', 'newuser@example.com')->first()->hasRole('cashier'))->toBeTrue();
});

test('admin can update a user role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $target = User::factory()->create();
    $target->assignRole('cashier');

    $this->actingAs($admin);

    \Livewire\Livewire::test('users.management')
        ->call('edit', $target->id)
        ->set('selectedRole', 'manager')
        ->call('save')
        ->assertHasNoErrors();

    expect($target->fresh()->hasRole('manager'))->toBeTrue();
});

test('admin can delete another user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $target = User::factory()->create();

    $this->actingAs($admin);

    \Livewire\Livewire::test('users.management')
        ->call('confirmDelete', $target->id)
        ->call('deleteUser');

    $this->assertDatabaseMissing('users', ['id' => $target->id]);
});

test('admin cannot delete own account', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    \Livewire\Livewire::test('users.management')
        ->call('confirmDelete', $admin->id)
        ->call('deleteUser');

    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('create user validates required fields', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    \Livewire\Livewire::test('users.management')
        ->call('save')
        ->assertHasErrors(['name', 'email', 'password']);
});
