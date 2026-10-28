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
        Schema::table('damage_scrap_inventory', function (Blueprint $table) {
            $table->integer('pieces_count')->nullable()->after('weight_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('damage_scrap_inventory', function (Blueprint $table) {
            $table->dropColumn(['pieces_count']);
        });
    }
};
