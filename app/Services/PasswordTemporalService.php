<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Genera y asigna contraseñas temporales.
 * Lo usan: alta de paciente en cita manual, restablecer contraseña y el módulo Personal.
 */
class PasswordTemporalService
{
    // Contraseña aleatoria, distinta para cada cuenta
    public function generar(): string
    {
        return Str::random(10);
    }

    /**
     * Asigna una contraseña temporal nueva y deja constancia de quién la restableció.
     * Devuelve los datos que necesita el panel de la vista.
     */
    public function restablecer(User $usuario, $restablecidaPor): array
    {
        $password = $this->generar();

        $usuario->password = Hash::make($password);
        $usuario->password_restablecida_por = $restablecidaPor;
        $usuario->password_restablecida_en = now();
        // Invalida las cookies de "recordarme" de sesiones anteriores
        $usuario->setRememberToken(Str::random(60));
        $usuario->save();

        $this->cerrarOtrasSesiones($usuario);

        return $this->panel($usuario, $password);
    }

    // Datos con los que la vista arma el panel de contraseña temporal
    public function panel(User $usuario, string $password): array
    {
        return [
            'password' => $password,
            'nombre' => trim($usuario->name . ' ' . $usuario->apellidos),
            'email' => $usuario->email,
        ];
    }

    /**
     * Cierra las sesiones abiertas del usuario (se guardan en base de datos).
     * $sesionActual se conserva: sirve cuando la persona cambia su propia
     * contraseña y no debe expulsarse a sí misma.
     */
    public function cerrarOtrasSesiones(User $usuario, ?string $sesionActual = null): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $consulta = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $usuario->id);

        if ($sesionActual) {
            $consulta->where('id', '!=', $sesionActual);
        }

        $consulta->delete();
    }
}
