<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'employee_no',
        'first_name',
        'last_name',
        'middle_name',
        'phone',
        'email',
        'address',
        'position',
        'department',
        'employment_type',
        'date_hired',
        'basic_salary',
        'salary_type',
        'status',
        'notes',
    ];

    protected $casts = [
        'date_hired'   => 'date',
        'basic_salary' => 'decimal:2',
    ];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(EmployeeDeduction::class);
    }

    public function cashAdvances(): HasMany
    {
        return $this->hasMany(EmployeeCashAdvance::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    public static function generateEmployeeNo(): string
    {
        $prefix = 'EMP-';
        $last = static::where('employee_no', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('employee_no');
        $next = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
