<?php

namespace App\Services\Chat;

use App\Models\Servicio;
use Carbon\Carbon;

/**
 * Validación y normalización de TODO lo que pide el modelo. Nunca se confía en sus ids ni en
 * sus datos. Los métodos devuelven el valor limpio o null; no dicen qué valor era (los errores
 * hacia el modelo no repiten lo escrito por el visitante).
 */
class DatosCita
{
    /** Servicio activo de una especialidad activa: los mismos que ve el paciente al agendar */
    public function servicio(mixed $id): ?Servicio
    {
        if (is_string($id) && preg_match('/^\d{1,9}$/', $id)) {
            $id = (int) $id;
        }

        if (!is_int($id) || $id < 1) {
            return null;
        }

        return Servicio::where('id', $id)
            ->where('activo', true)
            ->whereHas('especialidad', fn ($q) => $q->where('activo', true))
            ->first();
    }

    /** Hoy en la zona horaria del consultorio (la app corre en UTC) */
    public function hoy(): Carbon
    {
        return Carbon::now((string) config('consultorio.zona_horaria'))->startOfDay();
    }

    /**
     * @return array{fecha?: string, error?: string} 'fecha' en AAAA-MM-DD, o el motivo del rechazo:
     *         formato, pasada (hoy cuenta como no disponible) o lejana
     */
    public function fecha(mixed $valor): array
    {
        if (!is_string($valor) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return ['error' => 'formato'];
        }

        $fecha = Carbon::createFromFormat('!Y-m-d', $valor);

        if ($fecha === false || $fecha->format('Y-m-d') !== $valor) {
            return ['error' => 'formato'];
        }

        $hoy = $this->hoy()->toDateString();
        $desde = $this->hoy()->addDays((int) config('chat.agenda.dias_minimos'))->toDateString();
        $hasta = $this->hoy()->addMonths((int) config('chat.agenda.meses_maximos'))->toDateString();

        if ($valor < $desde) {
            return ['error' => 'pasada'];
        }

        return $valor > $hasta ? ['error' => 'lejana'] : ['fecha' => $valor];
    }

    /** HH:MM en 24 horas */
    public function hora(mixed $valor): ?string
    {
        if (!is_string($valor) || !preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', trim($valor), $m)) {
            return null;
        }

        return sprintf('%02d:%s', (int) $m[1], $m[2]);
    }

    /**
     * Teléfono mexicano de 10 dígitos. Se aceptan espacios, guiones, paréntesis y la lada
     * 52 / +52 / 521. Se rechazan los que no empiezan en 2-9 y los obviamente falsos.
     */
    public function telefono(mixed $valor): ?string
    {
        if (!is_string($valor) && !is_int($valor)) {
            return null;
        }

        $digitos = (string) preg_replace('/\D+/', '', (string) $valor);

        if (strlen($digitos) === 13 && str_starts_with($digitos, '521')) {
            $digitos = substr($digitos, 3);
        } elseif (strlen($digitos) === 12 && str_starts_with($digitos, '52')) {
            $digitos = substr($digitos, 2);
        }

        if (!preg_match('/^[2-9]\d{9}$/', $digitos)
            || preg_match('/^(\d)\1{9}$/', $digitos)
            || in_array($digitos, ['2345678901', '3456789012', '9876543210', '8765432109'], true)) {
            return null;
        }

        return $digitos;
    }

    /** Nombre o apellidos: solo letras, espacios, guion, apóstrofo y punto; 2 a 60 caracteres */
    public function nombre(mixed $valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }

        $limpio = TextoPlano::limpiar($valor);
        $limpio = is_string($limpio) ? trim((string) preg_replace('/\s+/u', ' ', $limpio)) : '';
        $largo = mb_strlen($limpio);

        if ($largo < 2 || $largo > 60
            || !preg_match('/^\p{L}[\p{L}\p{M} \'’.\-]*$/u', $limpio)
            || preg_match('/\.(?=\S)/u', $limpio)               // "www.sitio.com": el punto solo va al final de una abreviatura
            || preg_match_all('/\p{L}/u', $limpio) < 2) {
            return null;
        }

        return $limpio;
    }

    public function fechaLegible(string $fecha): string
    {
        return Carbon::createFromFormat('!Y-m-d', $fecha)->locale('es')->translatedFormat('l j \d\e F \d\e Y');
    }

    public function horaLegible(string $hora): string
    {
        return Carbon::createFromFormat('H:i', $hora)->format('h:i A');
    }

    public function telefonoLegible(string $telefono): string
    {
        return substr($telefono, 0, 3) . ' ' . substr($telefono, 3, 3) . ' ' . substr($telefono, 6);
    }
}
