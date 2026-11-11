<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Testimonio de un paciente con cuenta. Pendiente hasta que la doctora lo aprueba.
 * Todo texto se escapa al mostrarse; el nombre público sale de la cuenta (nombre de pila
 * e inicial del apellido), nunca se guarda aparte.
 */
class Testimonio extends Model
{
    protected $table = 'testimonios';

    const PENDIENTE = 'pendiente';
    const APROBADO = 'aprobado';
    const OCULTO = 'oculto';
    const ESTADOS = [self::PENDIENTE, self::APROBADO, self::OCULTO];

    // 'estado' y 'destacado' los cambian solo el servicio y la doctora: no son asignables en masa
    protected $fillable = ['paciente_id', 'calificacion', 'texto', 'autorizado'];

    protected $casts = [
        'calificacion' => 'integer',
        'autorizado' => 'boolean',
        'destacado' => 'boolean',
        'aprobado_en' => 'datetime',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paciente_id');
    }

    public function scopeAprobados($query)
    {
        return $query->where('estado', self::APROBADO);
    }

    /** "María G." — nombre de pila e inicial del apellido (solo si es una letra) */
    public function nombrePublico(): string
    {
        $paciente = $this->paciente;
        $nombre = trim((string) $paciente?->name);
        $apellido = trim((string) $paciente?->apellidos);

        if ($apellido !== '' && preg_match('/^\p{L}/u', $apellido)) {
            $nombre .= ' ' . mb_strtoupper(mb_substr($apellido, 0, 1)) . '.';
        }

        return $nombre;
    }

    /** Lo que ve el paciente de su propio estado (un testimonio oculto no se anuncia como tal) */
    public function etiquetaParaPaciente(): string
    {
        return match ($this->estado) {
            self::APROBADO => 'Publicado',
            self::OCULTO => 'No publicado',
            default => 'En revisión',
        };
    }
}
