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

            return match (Auth::user()->role) {
                'admin'     => redirect()->route('admin.dashboard'),
                'asistente' => redirect()->route('asistente.dashboard'),
                default     => redirect()->route('paciente.dashboard'),
            };
        }

        return back()->withErrors([
            'identificador' => 'Los datos no coinciden con nuestros registros.',
        ])->onlyInput('identificador');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}