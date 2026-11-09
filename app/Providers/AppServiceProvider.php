<?php

namespace App\Providers;

use App\Services\IA\ProveedorIA;
use App\Services\IA\ProveedorIAFactory;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // El proveedor de IA lo elige IA_PROVEEDOR (config/ia.php); no es singleton para respetar la config de cada petición
        $this->app->bind(ProveedorIA::class, fn ($app) => $app->make(ProveedorIAFactory::class)->crear());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}