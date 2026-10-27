<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

       // Procesa el inicio de sesión (acepta correo o teléfono)
    public function login(Request $request)
    {
        $request->validate([
            'identificador' => 'required|string',
            'password' => 'required',
        ], [
            'identificador.required' => 'Escribe tu correo o teléfono.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        // ¿Es correo o teléfono?
        $campo = str_contains($request->identificador, '@') ? 'email' : 'telefono';

        $credenciales = [
            $campo => $request->identificador,
            'password' => $request->password,
        ];

        if (Auth::attempt($credenciales)) {
            // Las cuentas desactivadas no pueden iniciar sesión
            if (!Auth::user()->activo) {
                Auth::logout();

                return back()->withErrors([
                    'identificador' => 'Tu cuenta está desactivada. Comunícate con la clínica.',
                ])->onlyInput('identificador');
            }

            $request->session()->regenerate();

            $rol = Auth::user()->role;
            $this->descartarDestinoDeOtroRol($request, $rol);

            // Si venía de una página protegida (p. ej. agendar cita), vuelve ahí; si no, a su panel
            return redirect()->intended(match ($rol) {
                'admin'     => route('admin.dashboard'),
                'asistente' => route('asistente.dashboard'),
                default     => route('paciente.dashboard'),
            });
        }

        return back()->withErrors([
            'identificador' => 'Los datos no coinciden con nuestros registros.',
        ])->onlyInput('identificador');
    }

    // Un destino guardado del área de otro rol (un admin que tocó "Agendar mi cita" sin
    // sesión) terminaría en un 403: se descarta y entra a su propio panel
    private function descartarDestinoDeOtroRol(Request $request, string $rol): void
    {
        $destino = $request->session()->get('url.intended');

        if (!$destino) {
            return;
        }

        $ruta = '/' . ltrim((string) parse_url($destino, PHP_URL_PATH), '/');

        foreach (['admin', 'asistente', 'paciente'] as $area) {
            if ($area !== $rol && ($ruta === "/$area" || str_starts_with($ruta, "/$area/"))) {
                $request->session()->forget('url.intended');
                return;
            }
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}