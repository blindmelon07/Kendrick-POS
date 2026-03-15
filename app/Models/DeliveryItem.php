<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryItem extends Model
{
    /** @use HasFactory<\Database\Factories\DeliveryItemFactory> */
    use HasFactory;

    protected $fillable = [
        'delivery_order_id', 'product_id', 'product_name',
        'unit_id', 'unit_label',
        'expected_quantity', 'received_quantity', 'unit_cost', 'notes',
    ];

    public function casts(): array
    {
        return [
            'expected_quantity'  => 'decimal:3',
            'received_quantity'  => 'decimal:3',
            'unit_cost'          => 'decimal:2',
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Unit, $this> */
    public function unit(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Unit::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<DeliveryOrder, $this> */
    public function deliveryOrder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Product, $this> */
    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
