<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDeduction extends Model
{
    protected $fillable = [
        'employee_id',
        'type',
        'description',
        'amount',
        'is_recurring',
        'effective_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'is_recurring'   => 'boolean',
        'effective_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
