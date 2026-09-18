<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Models\Expediente;
use App\Services\PasswordTemporalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PerfilController extends Controller
{
    // Datos, seguridad y expediente en solo lectura
    public function index()
    {
        $paciente = auth()->user()->load([
            'expediente.odontograma',
            'notasEvolucion' => function ($q) {
                $q->orderBy('created_at', 'desc');
            },
            'notasEvolucion.cita.servicio.especialidad',
        ]);

        return view('paciente.perfil.index', compact('paciente'));
    }

    // Editar sus propios datos
    public function actualizarDatos(Request $request)
    {
        $paciente = auth()->user();

        $datos = $request->validateWithBag('datos', [
            'name' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($paciente->id)],
            'telefono' => ['nullable', 'string', 'max:20', Rule::unique('users', 'telefono')->ignore($paciente->id)],
            'sexo' => 'nullable|in:masculino,femenino',
            'fecha_nacimiento' => 'nullable|date|before:today|after:1900-01-01',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo válido.',
            'email.unique' => 'Ese correo ya está registrado.',
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'telefono.unique' => 'Ese teléfono ya está registrado.',
            'sexo.in' => 'Selecciona una opción válida de sexo.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'fecha_nacimiento.after' => 'Ingresa una fecha de nacimiento válida.',
        ]);

        // Solo se actualizan estos campos: el rol y el estado de la cuenta nunca se tocan aquí
        $cambioCorreo = $datos['email'] !== $paciente->email;

        $paciente->fill([
            'name' => $datos['name'],
            'apellidos' => $datos['apellidos'],
            'email' => $datos['email'],
            'telefono' => $datos['telefono'] ?? null,
            'sexo' => $datos['sexo'] ?? null,
            'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? null,
        ]);

        // Un correo nuevo vuelve a estar sin verificar
        if ($cambioCorreo) {
            $paciente->email_verified_at = null;
        }

        $paciente->save();

        if ($cambioCorreo) {
            // Los datos ya quedaron guardados: un fallo de correo no los revierte
            $mensaje = $paciente->enviarVerificacion()
                ? 'Datos actualizados. Te enviamos un correo de verificación a tu nueva dirección.'
                : 'Datos actualizados, pero no pudimos enviar el correo de verificación. Puedes reenviarlo desde el inicio.';

            return redirect()->route('paciente.perfil.index')->with('success', $mensaje);
        }

        return redirect()->route('paciente.perfil.index')
            ->with('success', 'Datos actualizados correctamente.');
    }

    // Cambiar su contraseña confirmando la actual
    public function cambiarPassword(Request $request, PasswordTemporalService $passwordTemporal)
    {
        $paciente = auth()->user();

        $request->validateWithBag('password', [
            'password_actual' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password_actual.required' => 'Escribe tu contraseña actual.',
            'password_actual.current_password' => 'La contraseña actual no es correcta.',
            'password.required' => 'Escribe tu contraseña nueva.',
            'password.min' => 'La contraseña nueva debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $paciente->password = Hash::make($request->password);
        // Invalida las cookies de "recordarme" de otros dispositivos
        $paciente->setRememberToken(Str::random(60));
        $paciente->save();

        // Se cierran las demás sesiones, pero no esta
        $passwordTemporal->cerrarOtrasSesiones($paciente, $request->session()->getId());
        $request->session()->regenerate();

        return redirect()->route('paciente.perfil.index')
            ->with('success', 'Contraseña actualizada. Se cerraron tus otras sesiones.');
    }
}
