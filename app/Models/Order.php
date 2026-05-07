<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'reference_no',
        'customer_id',
        'client_name',
        'client_phone',
        'client_email',
        'delivery_address',
        'delivery_date',
        'delivery_time',
        'delivery_notes',
        'vehicle_type',
        'driver_name',
        'driver_phone',
        'delivery_fee',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'payment_method',
        'payment_status',
        'paymongo_checkout_id',
        'paymongo_payment_id',
        'amount_paid',
        'status',
        'created_by',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'notes',
    ];

    protected $casts = [
        'delivery_date'   => 'date',
        'delivery_time'   => 'datetime:H:i',
        'cancelled_at'    => 'datetime',
        'delivery_fee'    => 'decimal:2',
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total'           => 'decimal:2',
        'amount_paid'     => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function getBalanceAttribute(): float
    {
        return (float) $this->total - (float) $this->amount_paid;
    }

    public static function generateReferenceNo(): string
    {
        $prefix = 'ORD-' . date('Ymd') . '-';
        $last = static::where('reference_no', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('reference_no');

        $next = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
