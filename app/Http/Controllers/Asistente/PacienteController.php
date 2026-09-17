<?php

namespace App\Http\Controllers\Asistente;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PasswordTemporalService;

class PacienteController extends Controller
{
    /**
     * La asistente solo puede generar una contraseña temporal para un paciente.
     * No tiene acceso al expediente ni al listado de pacientes.
     */
    public function restablecerPassword($id, PasswordTemporalService $passwordTemporal)
    {
        $paciente = User::where('role', 'paciente')->findOrFail($id);

        $panel = $passwordTemporal->restablecer($paciente, auth()->id());

        return back()
            ->with('success', 'Contraseña restablecida. La anterior ya no funciona.')
            ->with('password_temporal', $panel);
    }
}
