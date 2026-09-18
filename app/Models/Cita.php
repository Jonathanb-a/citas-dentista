<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    use HasFactory;

    protected $fillable = [
        'paciente_id',
        'servicio_id',
        'horario_id',
        'fecha',
        'hora',
        'status',
        'confirmado_por_paciente',
        'confirmacion_limite',
        'motivo',
        'notas_asistente',
    ];

    protected $casts = [
        'fecha' => 'date',
        'confirmado_por_paciente' => 'boolean',
        'confirmacion_limite' => 'datetime',
    ];

    public function paciente()
    {
        return $this->belongsTo(User::class, 'paciente_id');
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class);
    }

    public function horario()
    {
        return $this->belongsTo(Horario::class);
    }

    // Nota de evolución de la consulta (una por cita, puede no existir)
    public function notaEvolucion()
    {
        return $this->hasOne(NotaEvolucion::class);
    }

    public function historial()
    {
        return $this->hasMany(HistorialCita::class);
    }
}