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
        Schema::create('retailer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retailer_id')->constrained('retailer_shops')->cascadeOnDelete();
            $table->foreignId('sales_memo_id')->nullable()->constrained('sales_memos')->nullOnDelete();
            $table->foreignId('road_id')->constrained('roads')->cascadeOnDelete();
            $table->foreignId('vehicle_trip_id')->nullable()->constrained('vehicle_trips')->nullOnDelete();
            $table->string('payment_method')->default('CASH');
            $table->decimal('amount', 14, 2);
            $table->date('payment_date');
            $table->string('transaction_ref')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['retailer_id', 'payment_date']);
            $table->index(['road_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retailer_payments');
    }
};
