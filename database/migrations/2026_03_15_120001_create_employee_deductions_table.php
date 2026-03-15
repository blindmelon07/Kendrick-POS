<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['sss', 'philhealth', 'pagibig', 'tax', 'cash_advance', 'loan', 'other']);
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->boolean('is_recurring')->default(false);  // monthly/payroll recurring
            $table->date('effective_date')->nullable();        // one-time or start date
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_deductions');
    }
};
