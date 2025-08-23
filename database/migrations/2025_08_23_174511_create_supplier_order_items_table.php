<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('supplier_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_order_id')->constrained('supplier_orders')->cascadeOnDelete();
            $table->foreignId('sppg_intake_item_id')->constrained('sppg_intake_items')->restrictOnDelete();
            $table->string('name');                 // snapshot nama item
            $table->string('unit', 32);             // snapshot unit
            $table->decimal('qty_allocated', 12, 3);
            $table->decimal('price', 12, 2)->nullable();   // diisi supplier
            $table->decimal('subtotal', 14, 2)->nullable(); // price * qty
            $table->timestamps();

            $table->index(['sppg_intake_item_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('supplier_order_items');
    }
};
