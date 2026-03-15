<?php

use App\Models\DeliveryOrder;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('delivery form adds item successfully', function () {
    $supplier = Supplier::factory()->create();
    $product  = Product::factory()->create(['cost_price' => 100, 'is_active' => true]);
    $unit     = Unit::first();

    $component = Livewire::test('deliveries.delivery-form')
        ->set('supplierId', $supplier->id)
        ->set('addProductId', $product->id)
        ->set('addExpectedQty', 5)
        ->set('addUnitId', $unit?->id)
        ->set('addUnitCost', 100)
        ->call('addItem');

    $component->assertHasNoErrors()
        ->assertSet('items', fn ($items) => count($items) === 1);
});

test('delivery form saves delivery to database', function () {
    $supplier = Supplier::factory()->create();
    $product  = Product::factory()->create(['cost_price' => 50, 'is_active' => true]);

    Livewire::test('deliveries.delivery-form')
        ->set('supplierId', $supplier->id)
        ->set('addProductId', $product->id)
        ->set('addExpectedQty', 3)
        ->set('addUnitCost', 50)
        ->call('addItem')
        ->assertHasNoErrors(['addProductId', 'addExpectedQty', 'addUnitCost'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('deliveries.index'));

    expect(DeliveryOrder::count())->toBe(1);
    expect(DeliveryOrder::first()->supplier_id)->toBe($supplier->id);
});

test('delivery form requires supplier and at least one item', function () {
    Livewire::test('deliveries.delivery-form')
        ->call('save')
        ->assertHasErrors(['supplierId', 'items']);
});
