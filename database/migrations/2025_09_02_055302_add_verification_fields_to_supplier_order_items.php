<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('supplier_order_items', function (Blueprint $table) {
            $table->decimal('verified_qty', 12, 3)->nullable()->after('qty_real');
            $table->timestamp('verified_at')->nullable()->after('verified_qty');
            $table->foreignId('verified_by')->nullable()->after('verified_at')
                ->constrained('users')->nullOnDelete();
            $table->string('verification_note', 300)->nullable()->after('verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_order_items', function (Blueprint $table) {
            $table->dropColumn(['verified_qty', 'verified_at', 'verified_by', 'verification_note']);
        });
    }
};
