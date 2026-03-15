<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionItemFactory> */
    use HasFactory;

    protected $fillable = [
        'transaction_id', 'product_id', 'product_name', 'sku',
        'quantity', 'unit_price', 'discount_amount', 'subtotal',
    ];

    public function casts(): array
    {
        return [
            'quantity'        => 'decimal:3',
            'unit_price'      => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'subtotal'        => 'decimal:2',
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Transaction, $this> */
    public function transaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Product, $this> */
    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
