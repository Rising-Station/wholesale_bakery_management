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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('packaging_type')->constrained('units')->nullOnDelete();
            $table->foreignId('packaging_type_id')->nullable()->after('unit_id')->constrained('packaging_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['packaging_type_id']);
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['packaging_type_id', 'unit_id']);
        });
    }
};
