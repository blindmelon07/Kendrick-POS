<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'sku', 'name', 'description', 'category_id', 'unit_id',
        'cost_price', 'selling_price', 'stock_quantity', 'reorder_level', 'is_active',
    ];

    public function casts(): array
    {
        return [
            'cost_price'     => 'decimal:2',
            'selling_price'  => 'decimal:2',
            'stock_quantity' => 'decimal:3',
            'reorder_level'  => 'decimal:3',
            'is_active'      => 'boolean',
        ];
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->reorder_level;
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Category, $this> */
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Unit, $this> */
    public function unit(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<StockMovement, $this> */
    public function stockMovements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
