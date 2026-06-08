<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Expediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'apellidos'             => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'telefono'              => 'required|string|max:20',
            'password'              => 'required|min:6|confirmed',
        ], [
            'name.required'         => 'El nombre es obligatorio.',
            'apellidos.required'    => 'Los apellidos son obligatorios.',
            'email.required'        => 'El correo es obligatorio.',
            'email.email'           => 'Ingresa un correo válido.',
            'email.unique'          => 'Este correo ya está registrado.',
            'telefono.required'     => 'El teléfono es obligatorio.',
            'password.required'     => 'La contraseña es obligatoria.',
            'password.min'          => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed'    => 'Las contraseñas no coinciden.',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'apellidos' => $request->apellidos,
            'email'     => $request->email,
            'telefono'  => $request->telefono,
            'password'  => Hash::make($request->password),
            'role'      => 'paciente',
            'activo'    => true,
        ]);

        // Crear expediente vacío automáticamente
        Expediente::create([
            'paciente_id' => $user->id,
        ]);

        Auth::login($user);

        return redirect()->route('paciente.dashboard');
    }
}