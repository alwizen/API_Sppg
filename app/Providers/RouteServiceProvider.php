<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('sppg', function (Request $request) {
            $sppg = $request->attributes->get('sppg'); // di-set oleh middleware
            $perMinute = $sppg ? (int) $sppg->rate_limit_per_minute : 60;

            $key = $sppg ? ('sppg:' . $sppg->id) : ('ip:' . $request->ip());
            return Limit::perMinute($perMinute)->by($key);
        });

        parent::boot();
    }
}
