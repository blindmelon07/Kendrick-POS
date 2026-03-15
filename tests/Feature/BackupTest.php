<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'manager']);
    Role::firstOrCreate(['name' => 'cashier']);
    Storage::fake('local');
});

test('admin can access backup page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.backup'))
        ->assertOk();
});

test('manager cannot access backup page', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)
        ->get(route('admin.backup'))
        ->assertForbidden();
});

test('cashier cannot access backup page', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole('cashier');

    $this->actingAs($cashier)
        ->get(route('admin.backup'))
        ->assertForbidden();
});

test('backup page renders with empty state when no backups exist', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('admin.backup')
        ->assertSee('No backups found');
});

test('admin can see existing backups', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $backupName = config('backup.backup.name');
    Storage::disk('local')->put("{$backupName}/2026-03-14-12-00-00.zip", 'fake-zip-content');

    Livewire::actingAs($admin)
        ->test('admin.backup')
        ->assertSee('2026-03-14-12-00-00.zip');
});

test('admin can trigger database backup', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Artisan::shouldReceive('call')
        ->once()
        ->with('backup:run', ['--only-db' => true])
        ->andReturn(0);

    Livewire::actingAs($admin)
        ->test('admin.backup')
        ->call('runBackup', true)
        ->assertDispatched('notify');
});

test('admin can trigger full backup', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Artisan::shouldReceive('call')
        ->once()
        ->with('backup:run', [])
        ->andReturn(0);

    Livewire::actingAs($admin)
        ->test('admin.backup')
        ->call('runBackup', false)
        ->assertDispatched('notify');
});

test('admin can confirm and delete a backup', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $backupName = config('backup.backup.name');
    $path       = "{$backupName}/2026-03-14-12-00-00.zip";
    Storage::disk('local')->put($path, 'fake-zip-content');

    Livewire::actingAs($admin)
        ->test('admin.backup')
        ->call('confirmDelete', $path)
        ->assertSet('showDeleteModal', true)
        ->assertSet('targetFile', $path)
        ->call('deleteBackup')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('notify');

    Storage::disk('local')->assertMissing($path);
});

test('admin can open restore confirmation modal', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $backupName = config('backup.backup.name');
    $path       = "{$backupName}/2026-03-14-12-00-00.zip";
    Storage::disk('local')->put($path, 'fake-zip-content');

    Livewire::actingAs($admin)
        ->test('admin.backup')
        ->call('confirmRestore', $path)
        ->assertSet('showRestoreModal', true)
        ->assertSet('targetFile', $path);
});
