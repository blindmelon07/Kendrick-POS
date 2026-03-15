<?php

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;

test('stock-in increases product quantity and records movement', function () {
    $user    = User::factory()->create();
    $product = Product::factory()->create(['stock_quantity' => 10]);

    $before                  = (float) $product->stock_quantity;
    $product->stock_quantity += 20;
    $product->save();

    StockMovement::create([
        'product_id'      => $product->id,
        'type'            => 'in',
        'quantity'        => 20,
        'before_quantity' => $before,
        'after_quantity'  => 30,
        'reason'          => 'Purchase',
        'user_id'         => $user->id,
    ]);

    $product->refresh();

    expect((float) $product->stock_quantity)->toEqual(30.0);

    $movement = StockMovement::first();
    expect($movement->type)->toBe('in');
    expect((float) $movement->before_quantity)->toEqual(10.0);
    expect((float) $movement->after_quantity)->toEqual(30.0);
});

test('stock-out decreases product quantity and records movement', function () {
    $user    = User::factory()->create();
    $product = Product::factory()->create(['stock_quantity' => 50]);

    $before                  = (float) $product->stock_quantity;
    $product->stock_quantity -= 15;
    $product->save();

    StockMovement::create([
        'product_id'      => $product->id,
        'type'            => 'out',
        'quantity'        => 15,
        'before_quantity' => $before,
        'after_quantity'  => 35,
        'reason'          => 'Damaged goods',
        'user_id'         => $user->id,
    ]);

    $product->refresh();

    expect((float) $product->stock_quantity)->toEqual(35.0);
    expect(StockMovement::where('type', 'out')->count())->toBe(1);
});

test('stock adjustment sets product to exact quantity', function () {
    $user    = User::factory()->create();
    $product = Product::factory()->create(['stock_quantity' => 30]);

    $before     = (float) $product->stock_quantity;
    $newQty     = 25.0;
    $appliedQty = abs($newQty - $before);

    $product->update(['stock_quantity' => $newQty]);

    StockMovement::create([
        'product_id'      => $product->id,
        'type'            => 'adjustment',
        'quantity'        => $appliedQty,
        'before_quantity' => $before,
        'after_quantity'  => $newQty,
        'reason'          => 'Physical count correction',
        'user_id'         => $user->id,
    ]);

    $product->refresh();

    expect((float) $product->stock_quantity)->toEqual(25.0);
    expect(StockMovement::where('type', 'adjustment')->count())->toBe(1);
});

test('product isLowStock returns true when stock is at or below reorder level', function () {
    $product = Product::factory()->create([
        'stock_quantity' => 5,
        'reorder_level'  => 10,
    ]);

    expect($product->isLowStock())->toBeTrue();
});

test('product isLowStock returns false when stock is above reorder level', function () {
    $product = Product::factory()->create([
        'stock_quantity' => 20,
        'reorder_level'  => 10,
    ]);

    expect($product->isLowStock())->toBeFalse();
});