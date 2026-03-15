<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseOrderFactory> */
    use HasFactory;

    protected $fillable = [
        'po_number', 'supplier_id', 'status', 'subtotal', 'tax_amount',
        'total', 'notes', 'ordered_at', 'expected_at', 'created_by',
    ];

    public function casts(): array
    {
        return [
            'subtotal'   => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total'      => 'decimal:2',
            'ordered_at' => 'datetime',
            'expected_at' => 'datetime',
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Supplier, $this> */
    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this> */
    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<PurchaseOrderItem, $this> */
    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<DeliveryOrder, $this> */
    public function deliveries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public static function generatePoNumber(): string
    {
        return 'PO-' . date('Ymd') . '-' . str_pad((string) (static::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
