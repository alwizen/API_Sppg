<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sppg_intake_items', function (Blueprint $table) {
            $table->decimal('kitchen_unit_price', 12, 2)->nullable()->after('unit');
        });
    }
    public function down(): void
    {
        Schema::table('sppg_intake_items', function (Blueprint $table) {
            $table->dropColumn('kitchen_unit_price');
        });
    }
};
