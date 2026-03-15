<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'reference_no', 'cashier_id', 'subtotal', 'discount_amount', 'tax_amount',
        'total', 'payment_method', 'amount_tendered', 'change_amount',
        'status', 'voided_by', 'voided_at', 'void_reason', 'notes',
    ];

    public function casts(): array
    {
        return [
            'subtotal'        => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount'      => 'decimal:2',
            'total'           => 'decimal:2',
            'amount_tendered' => 'decimal:2',
            'change_amount'   => 'decimal:2',
            'voided_at'       => 'datetime',
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this> */
    public function cashier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'cashier_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this> */
    public function voidedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'voided_by');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<TransactionItem, $this> */
    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public static function generateReferenceNo(): string
    {
        return 'TXN-' . strtoupper(date('Ymd')) . '-' . str_pad((string) (static::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
