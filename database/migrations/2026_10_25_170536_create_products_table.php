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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('packaging_type')->default('PIECES');
            $table->unsignedInteger('units_per_cartoon')->default(1);
            $table->decimal('weight_per_unit_kg', 8, 3)->nullable();
            $table->unsignedInteger('pieces_per_packet')->nullable();
            $table->decimal('cost_price', 12, 2)->default(0.00);
            $table->decimal('wholesale_price', 12, 2)->default(0.00);
            $table->decimal('warehouse_stock', 12, 2)->default(0.00);
            $table->unsignedInteger('min_alert_quantity')->default(10);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'warehouse_stock']);
            $table->index(['category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
