<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Paksa semua URL (asset, link, livewire) pake HTTPS kalau di production/tunnel
        if (config('app.env') === 'production' || env('FORCE_HTTPS', false)) {
            \URL::forceScheme('https');
        }
    }
}
