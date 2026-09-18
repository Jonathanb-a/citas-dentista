<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Nota de evolución de una consulta: la escribe la doctora al atender.
 * Es un acto clínico, por eso la asistente solo puede leerlas.
 */
class NotaEvolucion extends Model
{
    use HasFactory;

    protected $table = 'notas_evolucion';

    protected $fillable = [
        'cita_id',
        'paciente_id',
        'procedimiento',
        'observaciones',
        'indicaciones',
        'proxima_cita_sugerida',
        'creado_por',
    ];

    // Campos que escribe la doctora; si todos vienen vacíos, no se guarda nada
    public const CAMPOS = ['procedimiento', 'observaciones', 'indicaciones', 'proxima_cita_sugerida'];

    public static function reglas(): array
    {
        return [
            'procedimiento' => 'nullable|string|max:2000',
            'observaciones' => 'nullable|string|max:2000',
            'indicaciones' => 'nullable|string|max:2000',
            'proxima_cita_sugerida' => 'nullable|string|max:255',
        ];
    }

    public static function mensajes(): array
    {
        return [
            'max' => 'El campo :attribute es demasiado largo.',
        ];
    }

    // ¿La doctora escribió algo, o dejó el formulario vacío?
    public static function tieneContenido(array $datos): bool
    {
        foreach (self::CAMPOS as $campo) {
            if (filled($datos[$campo] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Crea o actualiza la nota de una cita. Devuelve null si no había nada que guardar.
     * La usan tanto "marcar atendida" como "agregar o editar después".
     */
    public static function guardarDeCita(Cita $cita, array $datos, $autor): ?self
    {
        if (!self::tieneContenido($datos)) {
            return null;
        }

        return self::updateOrCreate(
            ['cita_id' => $cita->id],
            [
                'paciente_id' => $cita->paciente_id,
                'procedimiento' => $datos['procedimiento'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                'indicaciones' => $datos['indicaciones'] ?? null,
                'proxima_cita_sugerida' => $datos['proxima_cita_sugerida'] ?? null,
                'creado_por' => $autor,
            ]
        );
    }

    public function cita()
    {
        return $this->belongsTo(Cita::class);
    }

    public function paciente()
    {
        return $this->belongsTo(User::class, 'paciente_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
