<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Bloqueo extends Model
{
    protected $table = 'bloqueos';

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'hora_inicio',
        'hora_fin',
        'motivo',
        'creado_por',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    // Bloqueos que tocan la fecha dada
    public function scopeQueCubre($query, $fecha)
    {
        $fecha = Carbon::parse($fecha)->toDateString();

        return $query->whereDate('fecha_inicio', '<=', $fecha)
            ->whereDate('fecha_fin', '>=', $fecha);
    }

    // Bloqueos que aún no terminan (hoy o futuros)
    public function scopeVigentes($query)
    {
        return $query->whereDate('fecha_fin', '>=', Carbon::today()->toDateString());
    }

    public function esDiaCompleto(): bool
    {
        return $this->hora_inicio === null || $this->hora_fin === null;
    }

    /**
     * ¿Este bloqueo impide atender en el rango [inicio, fin) de un mismo día?
     * Mismo criterio de traslape que las citas: inicioA < finB y finA > inicioB.
     */
    public function chocaCon(Carbon $inicio, Carbon $fin): bool
    {
        $dia = $inicio->copy()->startOfDay();

        if ($dia->lt($this->fecha_inicio->copy()->startOfDay()) || $dia->gt($this->fecha_fin->copy()->startOfDay())) {
            return false;
        }

        if ($this->esDiaCompleto()) {
            return true;
        }

        $bloqueoInicio = Carbon::parse($dia->toDateString() . ' ' . $this->hora_inicio);
        $bloqueoFin = Carbon::parse($dia->toDateString() . ' ' . $this->hora_fin);

        return $inicio->lt($bloqueoFin) && $fin->gt($bloqueoInicio);
    }

    public function chocaConCita(Cita $cita): bool
    {
        $inicio = Carbon::parse($cita->fecha->toDateString() . ' ' . $cita->hora);
        $fin = $inicio->copy()->addMinutes($cita->servicio->duracion_minutos);

        return $this->chocaCon($inicio, $fin);
    }
}
