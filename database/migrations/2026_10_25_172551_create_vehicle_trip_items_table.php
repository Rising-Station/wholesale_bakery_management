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
        Schema::create('vehicle_trip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_trip_id')->constrained('vehicle_trips')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('loaded_quantity', 12, 2);
            $table->decimal('sold_quantity', 12, 2)->default(0.00);
            $table->decimal('damaged_quantity', 12, 2)->default(0.00);
            $table->decimal('exchanged_quantity', 12, 2)->default(0.00);
            $table->decimal('returned_quantity', 12, 2)->default(0.00);
            $table->timestamps();

            $table->index(['vehicle_trip_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_trip_items');
    }
};
