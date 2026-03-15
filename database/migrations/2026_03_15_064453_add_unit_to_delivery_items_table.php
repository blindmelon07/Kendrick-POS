<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('delivery_items', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('product_name')->constrained('units')->nullOnDelete();
            $table->string('unit_label', 50)->nullable()->after('unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_items', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['unit_id', 'unit_label']);
        });
    }
};
