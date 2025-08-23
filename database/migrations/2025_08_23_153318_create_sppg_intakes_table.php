<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sppg_intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sppg_id')->constrained('sppgs')->cascadeOnDelete();

            $table->string('po_number');                   // SPPG-SLAWI/2025-08-23/00001
            $table->date('requested_at')->nullable();
            $table->time('delivery_time')->nullable();
            $table->string('status')->default('Received'); // Received/Allocated/Quoted/MarkedUp/Invoiced
            $table->text('notes')->nullable();

            $table->timestamp('submitted_at')->nullable(); // anchor saat submit dari SPPG
            $table->string('external_id')->nullable();     // id PO di app SPPG (mis: 1)
            $table->json('external_meta')->nullable();     // payload aslinya (atau creator, dll.)
            $table->string('external_hash', 64)->nullable(); // untuk deteksi perubahan

            $table->timestamps();

            // Unik per SPPG supaya idempotent (hindari duplikasi ketika retry)
            $table->unique(['sppg_id', 'po_number']);

            // Index bantu
            $table->index(['status']);
            $table->index(['submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sppg_intakes');
    }
};
