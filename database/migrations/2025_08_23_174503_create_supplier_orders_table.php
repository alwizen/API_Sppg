<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('supplier_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sppg_intake_id')->constrained('sppg_intakes')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('status')->default('Draft'); // Draft -> Quoted -> Fulfilled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['sppg_intake_id', 'supplier_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('supplier_orders');
    }
};
