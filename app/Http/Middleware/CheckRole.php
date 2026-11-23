<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        // Si la cuenta fue desactivada con la sesión abierta, se cierra la sesión
        if (!auth()->user()->activo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors([
                'identificador' => 'Tu cuenta está desactivada. Comunícate con la clínica.',
            ]);
        }

        // Contraseña temporal: hasta que la cambie solo puede ver la pantalla para cambiarla
        if (auth()->user()->debe_cambiar_password && !$request->routeIs('password.cambiar*')) {
            return redirect()->route('password.cambiar');
        }

        if (!in_array(auth()->user()->role, $roles)) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }

        return $next($request);
    }
}
