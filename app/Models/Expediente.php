<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expediente extends Model
{
    use HasFactory;

    // Opciones válidas para los selects del expediente
    public const TIPOS_SANGRE = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public const FRECUENCIAS_CEPILLADO = [
        'Nunca',
        'Ocasional',
        '1 vez al día',
        '2 veces al día',
        '3 o más veces al día',
    ];

    protected $fillable = [
        'paciente_id',
        'fecha_nacimiento',
        'sexo',
        // Identificación
        'direccion',
        'contacto_emergencia_nombre',
        'contacto_emergencia_telefono',
        'tipo_sangre',
        // Antecedentes
        'antecedentes_familiares',
        'alergias',
        'enfermedades',
        'medicamentos',
        'cirugias_previas',
        'embarazada',
        // Hábitos
        'fuma',
        'alcohol',
        'bruxismo',
        'frecuencia_cepillado',
        'usa_hilo_dental',
        // Clínico
        'padecimiento_actual',
        'diagnostico',
        'plan_tratamiento',
        'notas_doctora',
        'observaciones',
        // Trazabilidad
        'actualizado_por',
        'ultima_actualizacion',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'embarazada' => 'boolean',
        'fuma' => 'boolean',
        'alcohol' => 'boolean',
        'bruxismo' => 'boolean',
        'usa_hilo_dental' => 'boolean',
        'ultima_actualizacion' => 'datetime',
    ];

    public function paciente()
    {
        return $this->belongsTo(User::class, 'paciente_id');
    }

    // Dientes marcados del odontograma (los sanos no se guardan)
    public function odontograma()
    {
        return $this->hasMany(Odontograma::class);
    }

    // Usuario que hizo el último cambio al expediente
    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }
}
