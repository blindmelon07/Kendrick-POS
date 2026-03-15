<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    /** @use HasFactory<\Database\Factories\StockMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id', 'type', 'quantity', 'before_quantity',
        'after_quantity', 'reason', 'reference', 'notes', 'user_id',
    ];

    public function casts(): array
    {
        return [
            'quantity'        => 'decimal:3',
            'before_quantity' => 'decimal:3',
            'after_quantity'  => 'decimal:3',
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Product, $this> */
    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this> */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
