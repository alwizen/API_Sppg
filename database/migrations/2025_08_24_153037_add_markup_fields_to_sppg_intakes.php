<?php
// database/migrations/xxxx_add_markup_fields_to_sppg_intakes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sppg_intakes', function (Blueprint $table) {
            $table->decimal('total_cost', 14, 2)->nullable();
            $table->decimal('markup_percent', 5, 2)->nullable();
            $table->decimal('total_markup', 14, 2)->nullable();
            $table->decimal('grand_total', 14, 2)->nullable();
            $table->timestamp('marked_up_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('sppg_intakes', function (Blueprint $table) {
            $table->dropColumn(['total_cost', 'markup_percent', 'total_markup', 'grand_total', 'marked_up_at']);
        });
    }
};
