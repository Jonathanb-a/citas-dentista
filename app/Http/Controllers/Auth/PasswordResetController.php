<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        Password::sendResetLink($request->only('email'));

        // Respuesta idéntica exista o no la cuenta:
        // así nadie puede averiguar qué correos están registrados
        return back()->with('success',
            'Si ese correo está registrado, te enviamos un enlace para restablecer tu contraseña. Revisa tu bandeja y la carpeta de spam.'
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