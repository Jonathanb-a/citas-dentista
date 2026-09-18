<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    // Formulario: "escribe tu correo"
    public function solicitarFormulario()
    {
        return view('auth.forgot-password');
    }

    // Envía el correo con el link de recuperación
    public function enviarLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Escribe tu correo.',
            'email.email' => 'Escribe un correo válido.',
        ]);

        // Un fallo del servicio de correo no debe convertirse en un error 500
        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Throwable $error) {
            Log::error('No se pudo enviar el enlace de recuperación de contraseña.', [
                'error' => $error->getMessage(),
            ]);
        }

        // Respuesta idéntica exista o no la cuenta, y haya salido o no el correo:
        // así nadie puede averiguar qué correos están registrados.
        // La salida real si no llega es la clínica, que puede generar una contraseña temporal.
        return back()->with('success',
            'Si el correo está registrado, te enviamos un enlace. Si no te llega en unos minutos, comunícate con la clínica para que te generen una contraseña temporal.'
        );
    }

    // Formulario: "nueva contraseña" (desde el link del correo)
    public function restablecerFormulario(Request $request, $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    // Guarda la nueva contraseña
    public function restablecer(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ], [
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Tu contraseña fue restablecida. Ya puedes iniciar sesión.')
            : back()->withErrors(['email' => 'El enlace no es válido o ya expiró. Solicita uno nuevo.']);
    }
}