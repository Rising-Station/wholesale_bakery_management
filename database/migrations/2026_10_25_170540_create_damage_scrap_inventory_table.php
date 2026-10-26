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
        Schema::create('damage_scrap_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('retailer_id')->nullable()->constrained('retailer_shops')->nullOnDelete();
            $table->foreignId('road_id')->nullable()->constrained('roads')->nullOnDelete();
            $table->string('damage_type')->default('DAMAGED');
            $table->decimal('quantity', 12, 2);
            $table->string('status')->default('QUARANTINED');
            $table->date('disposal_date')->nullable();
            $table->text('disposal_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['status', 'damage_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('damage_scrap_inventory');
    }
};
