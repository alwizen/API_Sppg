<?php

namespace Database\Seeders;

use App\Models\Sppg;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SppgSeeder extends Seeder
{
    public function run(): void
    {
        // Bisa dioverride saat run: SEED_SPPG_CODE, SEED_SPPG_NAME, SEED_SPPG_API_KEY, SEED_SPPG_HMAC_SECRET
        $code       = env('SEED_SPPG_CODE', 'SPPG-SLAWI');
        $name       = env('SEED_SPPG_NAME', 'SPPG Slawi');
        $apiKey     = env('SEED_SPPG_API_KEY') ?: Str::random(48);
        $hmacSecret = env('SEED_SPPG_HMAC_SECRET') ?: Str::random(64);

        $sppg = Sppg::updateOrCreate(
            ['code' => $code],
            [
                'name'                  => $name,
                'api_key'               => $apiKey,
                'hmac_secret'           => $hmacSecret,
                'is_active'             => true,
                'rate_limit_per_minute' => 60,
                'timezone'              => 'Asia/Jakarta',
            ]
        );

        // Tampilkan kredensial di console agar mudah disalin ke .env aplikasi SPPG
        $this->command->warn('SPPG created/updated:');
        $this->command->line('  code       : ' . $sppg->code);
        $this->command->line('  api_key    : ' . $sppg->api_key);
        $this->command->line('  hmac_secret: ' . $sppg->hmac_secret);
    }
}
