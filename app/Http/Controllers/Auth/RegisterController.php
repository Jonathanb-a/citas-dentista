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
            'telefono'              => 'required|string|max:20|unique:users,telefono',
            'sexo'                  => 'required|in:masculino,femenino',
            'password'              => 'required|min:8|confirmed',
        ], [
            'name.required'         => 'El nombre es obligatorio.',
            'apellidos.required'    => 'Los apellidos son obligatorios.',
            'email.required'        => 'El correo es obligatorio.',
            'email.email'           => 'Ingresa un correo válido.',
            'email.unique'          => 'Este correo ya está registrado.',
            'telefono.required'     => 'El teléfono es obligatorio.',
            'telefono.unique'       => 'Ese teléfono ya está registrado.',
            'sexo.required'         => 'Selecciona tu sexo.',
            'sexo.in'               => 'Selecciona una opción válida de sexo.',
            'password.required'     =>'La contraseña es obligatoria.',
            'password.min'          => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'    => 'Las contraseñas no coinciden.',
        ]);

        $user = new User();
        $user->name      = $request->name;
        $user->apellidos = $request->apellidos;
        $user->email     = $request->email;
        $user->telefono  = $request->telefono;
        $user->sexo      = $request->sexo;
        $user->password  = Hash::make($request->password);
        $user->role      = 'paciente';   // asignado en código, nunca desde el formulario
        $user->activo    = true;
        $user->save();

        // Expediente clínico vacío creado automáticamente
        Expediente::create(['paciente_id' => $user->id]);

        // Correo de verificación: si falla, se registra en el log y el registro sigue.
        // El aviso del dashboard le permite reenviarlo después.
        $user->enviarVerificacion();

        Auth::login($user);

        return redirect()->route('paciente.dashboard');
    }
}