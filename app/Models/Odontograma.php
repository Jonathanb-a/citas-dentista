<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Estado de un diente dentro del odontograma de un expediente.
 * Solo se guardan los dientes marcados; sin registro = sano.
 */
class Odontograma extends Model
{
    use HasFactory;

    protected $table = 'odontograma';

    protected $fillable = [
        'expediente_id',
        'numero_diente',
        'estado',
        'nota',
        'actualizado_por',
    ];

    protected $casts = [
        'numero_diente' => 'integer',
    ];

    /**
     * Estados posibles de un diente, con su color.
     * Esta es la única fuente de verdad: de aquí salen la validación,
     * la leyenda, el resumen y el modal de la vista.
     */
    public const ESTADOS = [
        'sano' => ['etiqueta' => 'Sano', 'relleno' => '#ffffff', 'borde' => '#d1d5db', 'textoClaro' => false],
        'caries' => ['etiqueta' => 'Caries', 'relleno' => '#dc2626', 'borde' => '#b91c1c', 'textoClaro' => true],
        'obturado' => ['etiqueta' => 'Obturado', 'relleno' => '#2563eb', 'borde' => '#1d4ed8', 'textoClaro' => true],
        'corona' => ['etiqueta' => 'Corona', 'relleno' => '#f59e0b', 'borde' => '#d97706', 'textoClaro' => false],
        'ausente' => ['etiqueta' => 'Ausente / extraído', 'relleno' => '#4b5563', 'borde' => '#374151', 'textoClaro' => true],
        'endodoncia' => ['etiqueta' => 'Endodoncia', 'relleno' => '#7c3aed', 'borde' => '#6d28d9', 'textoClaro' => true],
        'implante' => ['etiqueta' => 'Implante', 'relleno' => '#15626C', 'borde' => '#0f4a52', 'textoClaro' => true],
        'fracturado' => ['etiqueta' => 'Fracturado', 'relleno' => '#ea580c', 'borde' => '#c2410c', 'textoClaro' => true],
        'en_tratamiento' => ['etiqueta' => 'En tratamiento', 'relleno' => '#d75078', 'borde' => '#be3f64', 'textoClaro' => true],
    ];

    // Cuadrantes de la dentadura permanente, en el orden en que se dibujan
    public const CUADRANTES = [
        'superior_derecho' => [18, 17, 16, 15, 14, 13, 12, 11],
        'superior_izquierdo' => [21, 22, 23, 24, 25, 26, 27, 28],
        'inferior_derecho' => [48, 47, 46, 45, 44, 43, 42, 41],
        'inferior_izquierdo' => [31, 32, 33, 34, 35, 36, 37, 38],
    ];

    /**
     * Números FDI que se aceptan hoy: solo dentición permanente.
     * Fase 3: al sumar los temporales (51-55, 61-65, 71-75, 81-85) el caso
     * a resolver es la dentición mixta, cuando el mismo paciente tiene
     * dientes permanentes y temporales al mismo tiempo (niños de 6 a 12 años).
     */
    public static function numerosValidos(): array
    {
        return array_merge(...array_values(self::CUADRANTES));
    }

    // Tipo de diente según el último dígito del número FDI (define su forma)
    public static function tipo(int $numero): string
    {
        return match ($numero % 10) {
            1, 2 => 'incisivo',
            3 => 'canino',
            4, 5 => 'premolar',
            default => 'molar',
        };
    }

    public static function etiqueta(string $estado): string
    {
        return self::ESTADOS[$estado]['etiqueta'] ?? $estado;
    }

    public function expediente()
    {
        return $this->belongsTo(Expediente::class);
    }

    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }
}
