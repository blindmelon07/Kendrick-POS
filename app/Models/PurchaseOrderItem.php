<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseOrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'purchase_order_id', 'product_id', 'product_name', 'quantity', 'unit_cost', 'subtotal',
    ];

    public function casts(): array
    {
        return [
            'quantity'  => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'subtotal'  => 'decimal:2',
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Product, $this> */
    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
