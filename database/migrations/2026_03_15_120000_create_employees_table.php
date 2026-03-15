<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->enum('employment_type', ['regular', 'probationary', 'contractual', 'part_time'])->default('regular');
            $table->date('date_hired')->nullable();
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->enum('salary_type', ['daily', 'monthly'])->default('daily');
            $table->enum('status', ['active', 'inactive', 'terminated', 'resigned'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
