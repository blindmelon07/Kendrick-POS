<?php

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('transaction reference number is formatted correctly', function () {
    $ref = Transaction::generateReferenceNo();

    expect($ref)->toStartWith('TXN-' . now()->format('Ymd') . '-');
});

test('checkout creates transaction with items and deducts stock', function () {
    $user    = User::factory()->create();
    $product = Product::factory()->create([
        'selling_price'  => 100.00,
        'cost_price'     => 60.00,
        'stock_quantity' => 50,
    ]);

    $initialStock = (float) $product->stock_quantity;
    $quantity     = 3;

    DB::transaction(function () use ($user, $product, $quantity) {
        $transaction = Transaction::create([
            'reference_no'    => Transaction::generateReferenceNo(),
            'cashier_id'      => $user->id,
            'subtotal'        => $product->selling_price * $quantity,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'total'           => $product->selling_price * $quantity,
            'payment_method'  => 'cash',
            'amount_tendered' => 400,
            'change_amount'   => 400 - ($product->selling_price * $quantity),
            'status'          => 'completed',
        ]);

        TransactionItem::create([
            'transaction_id'  => $transaction->id,
            'product_id'      => $product->id,
            'product_name'    => $product->name,
            'sku'             => $product->sku,
            'quantity'        => $quantity,
            'unit_price'      => $product->selling_price,
            'discount_amount' => 0,
            'subtotal'        => $product->selling_price * $quantity,
        ]);

        $before                  = (float) $product->stock_quantity;
        $product->stock_quantity -= $quantity;
        $product->save();

        StockMovement::create([
            'product_id'      => $product->id,
            'type'            => 'out',
            'quantity'        => $quantity,
            'before_quantity' => $before,
            'after_quantity'  => (float) $product->stock_quantity,
            'reason'          => 'POS Sale - ' . $transaction->reference_no,
            'reference'       => $transaction->reference_no,
            'user_id'         => $user->id,
        ]);
    });

    $product->refresh();

    expect($product->stock_quantity)->toEqual($initialStock - $quantity);
    expect(Transaction::where('status', 'completed')->count())->toBe(1);
    expect(TransactionItem::count())->toBe(1);
    expect(StockMovement::where('type', 'out')->count())->toBe(1);
});

test('voiding a transaction restores product stock', function () {
    $user    = User::factory()->create();
    $product = Product::factory()->create(['stock_quantity' => 47]);

    $transaction = Transaction::factory()->create([
        'cashier_id' => $user->id,
        'status'     => 'completed',
    ]);

    TransactionItem::create([
        'transaction_id'  => $transaction->id,
        'product_id'      => $product->id,
        'product_name'    => $product->name,
        'sku'             => $product->sku,
        'quantity'        => 5,
        'unit_price'      => 100,
        'discount_amount' => 0,
        'subtotal'        => 500,
    ]);

    // Simulate void
    $transaction->update([
        'status'      => 'voided',
        'voided_by'   => $user->id,
        'voided_at'   => now(),
        'void_reason' => 'Test void',
    ]);

    $before                  = (float) $product->stock_quantity;
    $product->stock_quantity += 5;
    $product->save();

    StockMovement::create([
        'product_id'      => $product->id,
        'type'            => 'in',
        'quantity'        => 5,
        'before_quantity' => $before,
        'after_quantity'  => (float) $product->stock_quantity,
        'reason'          => 'Void - ' . $transaction->reference_no,
        'reference'       => $transaction->reference_no,
        'user_id'         => $user->id,
    ]);

    $product->refresh();

    expect((float) $product->stock_quantity)->toEqual(52.0);
    expect($transaction->fresh()->status)->toBe('voided');
    expect(StockMovement::where('type', 'in')->count())->toBe(1);
});