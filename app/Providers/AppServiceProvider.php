<?php

namespace App\Providers;

use App\Services\CorreoResend;
use App\Services\IA\ProveedorIA;
use App\Services\IA\ProveedorIAFactory;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
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
        // Una sola instancia: el aviso de 'falta la clave' sale una vez por proceso
        $this->app->singleton(CorreoResend::class);
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

        // Correo por la API de Resend con tiempos límite (el transporte del paquete no los trae)
        Mail::extend('resend', fn (array $config) => $this->app->make(CorreoResend::class)->transporte($config));

        // Testimonios: crear, editar y borrar, por usuario y por hora (al pasarse, 429)
        RateLimiter::for('testimonios', fn (Request $request) => Limit::perHour((int) config('testimonios.limite_hora'))
            ->by('testimonio:' . ($request->user()?->id ?? $request->ip())));

        // Chat público: por IP, mensajes por minuto y por día (ventanas móviles). Al pasarse, 429 con
        // un mensaje listo para mostrar. La IP es la última entrada de X-Forwarded-For (la que agrega
        // el proxy de Railway), que el visitante no puede falsificar
        RateLimiter::for('chat', function (Request $request) {
            $ip = (string) $request->ip();
            $respuesta = fn (string $mensaje) => fn (Request $r, array $cabeceras) => response()->json(
                ['ok' => false, 'respuesta' => config('chat.mensajes.' . $mensaje)],
                429,
                $cabeceras,
            );

            return [
                Limit::perMinute((int) config('chat.limite_minuto'))->by('chat-min:' . $ip)->response($respuesta('limite_minuto')),
                Limit::perDay((int) config('chat.limite_dia'))->by('chat-dia:' . $ip)->response($respuesta('limite_dia')),
            ];
        });
    }
}