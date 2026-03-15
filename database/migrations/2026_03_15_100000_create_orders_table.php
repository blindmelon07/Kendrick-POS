<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();

            // Client info
            $table->string('client_name');
            $table->string('client_phone')->nullable();
            $table->string('client_email')->nullable();

            // Delivery info
            $table->text('delivery_address');
            $table->date('delivery_date')->nullable();
            $table->time('delivery_time')->nullable();
            $table->text('delivery_notes')->nullable();

            // Shipping details (customizable per delivery)
            $table->string('vehicle_type')->nullable();       // e.g. truck, pickup, etc.
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->decimal('delivery_fee', 12, 2)->default(0);

            // Financials
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Payment
            $table->enum('payment_method', ['cash', 'gcash', 'credit_card', 'bank_transfer', 'on_delivery'])->default('on_delivery');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->decimal('amount_paid', 12, 2)->default(0);

            // Status
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'])->default('pending');

            // Who handled it
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
