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
        Schema::create('sales_memos', function (Blueprint $table) {
            $table->id();
            $table->string('memo_no')->unique();
            $table->foreignId('retailer_id')->constrained('retailer_shops')->cascadeOnDelete();
            $table->foreignId('road_id')->constrained('roads')->cascadeOnDelete();
            $table->foreignId('vehicle_trip_id')->nullable()->constrained('vehicle_trips')->nullOnDelete();
            $table->date('memo_date');
            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->decimal('damage_deduction', 14, 2)->default(0.00);
            $table->decimal('exchange_deduction', 14, 2)->default(0.00);
            $table->decimal('custom_deduction', 14, 2)->default(0.00);
            $table->decimal('grand_total', 14, 2)->default(0.00);
            $table->decimal('previous_due', 14, 2)->default(0.00);
            $table->decimal('total_net_payable', 14, 2)->default(0.00);
            $table->decimal('paid_amount', 14, 2)->default(0.00);
            $table->decimal('due_amount', 14, 2)->default(0.00);
            $table->string('memo_status')->default('PAID'); // PAID, PARTIAL, DUE, CANCELLED
            $table->string('delivery_status')->default('DELIVERED'); // DELIVERED, PENDING, RETURNED
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['road_id', 'memo_date']);
            $table->index(['retailer_id', 'memo_date']);
            $table->index('memo_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_memos');
    }
};
