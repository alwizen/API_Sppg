<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sppgs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();              // contoh: SPPG-SLAWI
            $table->string('name')->nullable();            // opsional nama sppg
            $table->string('api_key')->unique();           // API key per SPPG (untuk otorisasi)
            $table->string('hmac_secret')->nullable();     // opsional, untuk signature HMAC
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('rate_limit_per_minute')->default(60);
            $table->string('timezone')->default('Asia/Jakarta');

            $table->string('webhook_url')->nullable();     // opsional, callback ke SPPG
            $table->string('pull_base_url')->nullable();   // opsional, untuk rekonsiliasi/pull
            $table->json('ip_whitelist')->nullable();      // opsional

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sppgs');
    }
};
