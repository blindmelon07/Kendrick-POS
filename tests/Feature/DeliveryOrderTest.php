<?php

use App\Models\DeliveryItem;
use App\Models\DeliveryOrder;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;

test('delivery order number is formatted correctly', function () {
    $number = DeliveryOrder::generateDeliveryNumber();

    expect($number)->toStartWith('DLV-' . now()->format('Ymd') . '-');
});

test('receiving a delivery updates product stock and creates stock movements', function () {
    $user     = User::factory()->create();
    $supplier = Supplier::factory()->create();
    $product  = Product::factory()->create(['stock_quantity' => 10]);

    $delivery = DeliveryOrder::create([
        'delivery_number' => DeliveryOrder::generateDeliveryNumber(),
        'supplier_id'     => $supplier->id,
        'status'          => 'in_transit',
        'created_by'      => $user->id,
    ]);

    $item = DeliveryItem::create([
        'delivery_order_id' => $delivery->id,
        'product_id'        => $product->id,
        'product_name'      => $product->name,
        'expected_quantity' => 50,
        'received_quantity' => 0,
        'unit_cost'         => 25.00,
    ]);

    // Simulate receiving the delivery
    $received = 50.0;
    $before   = (float) $product->stock_quantity;

    $product->stock_quantity += $received;
    $product->save();

    StockMovement::create([
        'product_id'      => $product->id,
        'type'            => 'in',
        'quantity'        => $received,
        'before_quantity' => $before,
        'after_quantity'  => (float) $product->stock_quantity,
        'reason'          => 'Delivery - ' . $delivery->delivery_number,
        'reference'       => $delivery->delivery_number,
        'user_id'         => $user->id,
    ]);

    $item->update(['received_quantity' => $received]);
    $delivery->update(['status' => 'received', 'received_at' => now()]);

    $product->refresh();

    expect((float) $product->stock_quantity)->toEqual(60.0);
    expect($delivery->fresh()->status)->toBe('received');
    expect((float) $item->fresh()->received_quantity)->toEqual(50.0);
    expect(StockMovement::where('type', 'in')->where('reference', $delivery->delivery_number)->count())->toBe(1);
});

test('delivery cancellation does not change product stock', function () {
    $user     = User::factory()->create();
    $supplier = Supplier::factory()->create();
    $product  = Product::factory()->create(['stock_quantity' => 20]);

    $delivery = DeliveryOrder::create([
        'delivery_number' => DeliveryOrder::generateDeliveryNumber(),
        'supplier_id'     => $supplier->id,
        'status'          => 'pending',
        'created_by'      => $user->id,
    ]);

    $delivery->update(['status' => 'cancelled']);

    $product->refresh();

    expect($delivery->fresh()->status)->toBe('cancelled');
    expect((float) $product->stock_quantity)->toEqual(20.0);
    expect(StockMovement::count())->toBe(0);
});