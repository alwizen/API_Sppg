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
        Schema::table('sppg_intake_items', function (Blueprint $table) {
            $table->time('delivery_time_item')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sppg_intake_items', function (Blueprint $table) {
            $table->dropColumn('delivery_time_item');
        });
    }
};
