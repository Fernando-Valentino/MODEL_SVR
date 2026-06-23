<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

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
        // Atur locale Carbon ke Bahasa Indonesia agar translatedFormat()
        // menampilkan nama bulan & hari dalam Bahasa Indonesia (contoh: "Juni", "Senin")
        Carbon::setLocale('id');
        app()->setLocale('id');
    }
}
