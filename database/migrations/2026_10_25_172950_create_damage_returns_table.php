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
        Schema::create('damage_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_memo_id')->nullable()->constrained('sales_memos')->nullOnDelete();
            $table->foreignId('retailer_id')->constrained('retailer_shops')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('vehicle_trip_id')->nullable()->constrained('vehicle_trips')->nullOnDelete();
            $table->string('return_type')->default('DAMAGE'); // DAMAGE (Scenario 1) or EXCHANGE (Scenario 2)
            $table->string('damage_type')->nullable(); // DAMAGED, EXPIRED, TRANSIT_LOSS
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_deduction_rate', 12, 2);
            $table->decimal('total_deduction_amount', 14, 2);
            $table->boolean('affects_sellable_stock')->default(false);
            $table->boolean('is_custom_amount')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['return_type', 'created_at']);
            $table->index(['retailer_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('damage_returns');
    }
};
