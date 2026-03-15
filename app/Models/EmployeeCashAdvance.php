<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCashAdvance extends Model
{
    protected $fillable = [
        'employee_id',
        'amount',
        'amount_paid',
        'date_granted',
        'reason',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'amount_paid'  => 'decimal:2',
        'date_granted' => 'date',
    ];

    public function getBalanceAttribute(): float
    {
        return (float) $this->amount - (float) $this->amount_paid;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
