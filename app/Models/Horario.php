<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    use HasFactory;

    protected $fillable = [
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'descanso_minutos',
        'disponible',
    ];

    protected $casts = [
        'disponible' => 'boolean',
    ];

    public function citas()
    {
        return $this->hasMany(Cita::class);
    }
}