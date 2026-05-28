<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Revisi #4: pagination konsisten & rapi di seluruh halaman
        Paginator::defaultView('vendor.pagination.app');
        Paginator::defaultSimpleView('vendor.pagination.app');

        // Blade directive: @idr(value) — format IDR lengkap: Rp 100.000.000
        Blade::directive('idr', function ($expression) {
            return "<?php echo idr($expression); ?>";
        });

        // Blade directive: @idrm(value) — format IDR singkat: Rp 100M / Rp 1,5M
        Blade::directive('idrm', function ($expression) {
            return "<?php echo idrm($expression); ?>";
        });
    }
}
