<?php

use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('supplier list renders', function () {
    Supplier::factory()->create(['name' => 'ACME Corp']);

    Livewire::test('suppliers.management')
        ->assertSee('ACME Corp');
});

test('can create a supplier', function () {
    Livewire::test('suppliers.management')
        ->call('create')
        ->set('name', 'New Supplier Co')
        ->set('contactName', 'Juan Dela Cruz')
        ->set('email', 'juan@supplier.com')
        ->set('phone', '09171234567')
        ->call('save')
        ->assertHasNoErrors();

    expect(Supplier::where('name', 'New Supplier Co')->exists())->toBeTrue();
});

test('can edit a supplier', function () {
    $supplier = Supplier::factory()->create(['name' => 'Old Name']);

    Livewire::test('suppliers.management')
        ->call('edit', $supplier->id)
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($supplier->fresh()->name)->toBe('Updated Name');
});

test('cannot save supplier without name', function () {
    Livewire::test('suppliers.management')
        ->call('create')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

test('can delete a supplier with no orders', function () {
    $supplier = Supplier::factory()->create();

    Livewire::test('suppliers.management')
        ->call('confirmDelete', $supplier->id)
        ->call('deleteSupplier');

    expect(Supplier::find($supplier->id))->toBeNull();
});
