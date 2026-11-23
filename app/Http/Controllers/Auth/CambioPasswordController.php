<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordTemporalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Cambio obligatorio de la contraseña temporal. Lo ven las tres clases de cuenta (doctora, asistente
 * y paciente): CheckRole manda aquí a quien tenga `debe_cambiar_password` hasta que la cambie.
 */
class CambioPasswordController extends Controller
{
    public function show()
    {
        // Quien no tiene nada pendiente no tiene por qué estar aquí
        if (!auth()->user()->debe_cambiar_password) {
            return redirect($this->destino());
        }

        return view('auth.cambiar-password');
    }

    public function actualizar(Request $request, PasswordTemporalService $passwordTemporal)
    {
        $usuario = auth()->user();

        $request->validate([
            'password_actual' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:password_actual'],
        ], [
            'password_actual.required' => 'Escribe tu contraseña temporal.',
            'password_actual.current_password' => 'La contraseña temporal no es correcta.',
            'password.required' => 'Escribe tu contraseña nueva.',
            'password.min' => 'La contraseña nueva debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.different' => 'La contraseña nueva debe ser distinta de la temporal.',
        ]);

        $usuario->password = Hash::make($request->password);
        $usuario->debe_cambiar_password = false;
        // Invalida las cookies de "recordarme" de otros dispositivos
        $usuario->setRememberToken(Str::random(60));
        $usuario->save();

        // Se cierran las demás sesiones, pero no esta
        $passwordTemporal->cerrarOtrasSesiones($usuario, $request->session()->getId());
        $request->session()->regenerate();

        return redirect($this->destino())->with('success', 'Contraseña actualizada. ¡Bienvenido!');
    }

    private function destino(): string
    {
        return match (auth()->user()->role) {
            'admin' => route('admin.dashboard'),
            'asistente' => route('asistente.dashboard'),
            default => route('paciente.dashboard'),
        };
    }
}
