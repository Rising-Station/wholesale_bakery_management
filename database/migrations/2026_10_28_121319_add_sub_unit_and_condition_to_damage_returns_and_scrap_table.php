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
        Schema::table('damage_returns', function (Blueprint $table) {
            $table->string('return_unit')->nullable()->default('PACKAGE')->after('damage_type');
            $table->decimal('sub_unit_quantity', 12, 3)->nullable()->after('return_unit');
            $table->decimal('conversion_factor', 12, 3)->nullable()->default(1.000)->after('sub_unit_quantity');
            $table->string('condition_status')->default('GOOD_SEALED')->after('conversion_factor');
            $table->string('destination_location')->default('SELLABLE_STOCK')->after('condition_status');
        });

        Schema::table('damage_scrap_inventory', function (Blueprint $table) {
            $table->string('category')->default('SCRAP_DISPOSAL')->after('damage_type');
            $table->decimal('weight_kg', 12, 3)->nullable()->after('quantity');
            $table->string('return_unit')->nullable()->after('weight_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('damage_scrap_inventory', function (Blueprint $table) {
            $table->dropColumn(['category', 'weight_kg', 'return_unit']);
        });

        Schema::table('damage_returns', function (Blueprint $table) {
            $table->dropColumn([
                'return_unit',
                'sub_unit_quantity',
                'conversion_factor',
                'condition_status',
                'destination_location',
            ]);
        });
    }
};
