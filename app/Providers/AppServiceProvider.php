<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('contact', fn (Request $r) => Limit::perMinute(5)->by($r->ip()));
        RateLimiter::for('audit', fn (Request $r) => Limit::perHour(3)->by($r->ip()));
        RateLimiter::for('two-factor', fn (Request $r) => Limit::perMinute(5)->by(optional($r->user())->id ?: $r->ip()));
    }
}
