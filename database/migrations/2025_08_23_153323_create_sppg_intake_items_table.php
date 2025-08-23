<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sppg_intake_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sppg_intake_id')->constrained('sppg_intakes')->cascadeOnDelete();

            $table->unsignedBigInteger('external_item_id')->nullable(); // id item di SPPG (opsional)
            $table->string('name');                       // contoh: ayam, bakso
            $table->decimal('qty', 12, 3);                // simpan 12.000 → 12.000
            $table->string('unit', 32);                   // kg, butir, dll.
            $table->string('note', 255)->nullable();

            $table->timestamps();

            $table->index(['name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sppg_intake_items');
    }
};
