<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    /** @use HasFactory<\Database\Factories\DeliveryOrderFactory> */
    use HasFactory;

    protected $fillable = [
        'delivery_number', 'supplier_id', 'purchase_order_id', 'status',
        'notes', 'shipped_at', 'expected_at', 'received_at', 'created_by',
    ];

    public function casts(): array
    {
        return [
            'shipped_at'  => 'datetime',
            'expected_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Supplier, $this> */
    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this> */
    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<DeliveryItem, $this> */
    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public static function generateDeliveryNumber(): string
    {
        return 'DLV-' . date('Ymd') . '-' . str_pad((string) (static::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
