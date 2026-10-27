<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Aviso o promoción que la doctora publica en la landing page.
 * Todo el texto es plano: la vista lo escapa, nunca se imprime como HTML.
 */
class Anuncio extends Model
{
    const TIPOS = ['aviso' => 'Aviso', 'promocion' => 'Promoción'];

    const BOTON_TEXTO_DEFECTO = 'Agendar cita';
    const BOTON_ENLACE_DEFECTO = '#contacto';

    /**
     * Paleta fija. Las clases van completas (Tailwind no genera clases armadas
     * al vuelo) y el color nunca viene libre del formulario.
     */
    const COLORES = [
        'rosa' => [
            'nombre' => 'Rosa',
            'muestra' => 'bg-[#d75078]',
            'franja' => 'bg-[#fde7ed] text-[#a13153]', // 5.79:1
            'circulo' => 'bg-[#fde7ed]',
            'borde' => 'border-[#fde7ed]',
            'acento' => 'text-[#d75078]',
            'boton' => 'bg-[#c03a63] hover:bg-[#a13153]', // texto blanco: 5.22:1 y 6.82:1
        ],
        'verde' => [
            'nombre' => 'Verde',
            'muestra' => 'bg-[#15626C]',
            'franja' => 'bg-[#15626C]/10 text-[#15626C]',
            'circulo' => 'bg-[#15626C]/10',
            'borde' => 'border-[#15626C]/20',
            'acento' => 'text-[#15626C]',
            'boton' => 'bg-[#15626C] hover:bg-[#1d7f8b]',
        ],
        'gris' => [
            'nombre' => 'Gris',
            'muestra' => 'bg-gray-500',
            'franja' => 'bg-gray-100 text-gray-700',
            'circulo' => 'bg-gray-100',
            'borde' => 'border-gray-200',
            'acento' => 'text-gray-600',
            'boton' => 'bg-gray-700 hover:bg-gray-600',
        ],
    ];

    protected $fillable = [
        'titulo',
        'descripcion',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        'boton_texto',
        'boton_enlace',
        'emoji',
        'color',
        'imagen',
        'orden',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
    ];

    // Orden del carrusel: primero el que tiene el número más bajo
    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden')->orderBy('id');
    }

    // ---------- Vigencia ----------

    // "Hoy" en la zona del consultorio, no en UTC
    public static function hoy(): string
    {
        return Carbon::now(config('consultorio.zona_horaria'))->toDateString();
    }

    // Vigente = activo y dentro del rango de fechas (ambos extremos incluidos, ambos opcionales)
    public function scopeVigentes($query, ?string $hoy = null)
    {
        $hoy = $hoy ?? self::hoy();

        return $query->where('activo', true)
            ->where(fn ($q) => $q->whereNull('fecha_inicio')->orWhereDate('fecha_inicio', '<=', $hoy))
            ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $hoy));
    }

    /** inactivo | programado | vencido | vigente */
    public function estado(?string $hoy = null): string
    {
        $hoy = $hoy ?? self::hoy();

        if (!$this->activo) {
            return 'inactivo';
        }
        if ($this->fecha_inicio && $this->fecha_inicio->toDateString() > $hoy) {
            return 'programado';
        }
        if ($this->fecha_fin && $this->fecha_fin->toDateString() < $hoy) {
            return 'vencido';
        }

        return 'vigente';
    }

    // ---------- Presentación ----------

    // URL pública de la imagen subida, o null si no tiene
    public function imagenUrl(): ?string
    {
        return app(\App\Services\ImagenService::class)->url($this->imagen);
    }

    public function paleta(): array
    {
        return self::COLORES[$this->color] ?? self::COLORES['rosa'];
    }

    // Una promoción siempre lleva botón; un aviso solo si se escribió texto o enlace
    public function muestraBoton(): bool
    {
        return $this->tipo === 'promocion' || filled($this->boton_texto) || filled($this->boton_enlace);
    }

    public function textoBoton(): string
    {
        return $this->boton_texto ?: self::BOTON_TEXTO_DEFECTO;
    }

    public function enlaceBoton(): string
    {
        return $this->boton_enlace ?: self::BOTON_ENLACE_DEFECTO;
    }

    public function enlaceEsExterno(): bool
    {
        return (bool) preg_match('#^https?://#i', $this->enlaceBoton());
    }

    // ---------- Validación (la comparten crear y editar) ----------

    public static function reglas(): array
    {
        return [
            'titulo' => 'required|string|max:120',
            'descripcion' => 'nullable|string|max:300',
            'tipo' => 'required|in:' . implode(',', array_keys(self::TIPOS)),
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'activo' => 'nullable|boolean',
            'boton_texto' => 'nullable|string|max:40',
            'boton_enlace' => ['nullable', 'string', 'max:255', function ($atributo, $valor, $fallar) {
                if (!self::enlaceValido($valor)) {
                    $fallar('El enlace debe ser una dirección web (https://…), una ruta del sitio (/…), un ancla (#contacto) o un teléfono (tel:…).');
                }
            }],
            'emoji' => ['nullable', 'string', 'max:16', function ($atributo, $valor, $fallar) {
                if (!preg_match('/^[\p{So}\p{Sk}\p{Mn}\p{Cf}]{1,10}$/u', $valor)) {
                    $fallar('Escribe solo un emoji (por ejemplo 🦷 o ✨).');
                }
            }],
            'color' => 'required|in:' . implode(',', array_keys(self::COLORES)),
            'imagen' => \App\Services\ImagenService::reglas(),
            'quitar_imagen' => 'nullable|boolean',
        ];
    }

    public static function mensajes(): array
    {
        return [
            'titulo.required' => 'El título es obligatorio.',
            'titulo.max' => 'El título no puede pasar de 120 caracteres.',
            'descripcion.max' => 'La descripción no puede pasar de 300 caracteres.',
            'tipo.required' => 'Elige si es un aviso o una promoción.',
            'tipo.in' => 'Elige si es un aviso o una promoción.',
            'fecha_fin.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'boton_texto.max' => 'El texto del botón no puede pasar de 40 caracteres.',
            'color.in' => 'Elige un color de la lista.',
        ] + \App\Services\ImagenService::mensajes();
    }

    /**
     * Solo http(s), rutas internas, anclas y teléfonos. Cualquier otro esquema
     * (javascript:, data:, …), rutas "//host" y barras invertidas se rechazan.
     */
    public static function enlaceValido(?string $enlace): bool
    {
        if ($enlace === null || $enlace === '') {
            return true;
        }

        if (preg_match('/[\s\\\\\x00-\x1f]/', $enlace)) {
            return false;
        }

        if (preg_match('#^https?://#i', $enlace)) {
            return filter_var($enlace, FILTER_VALIDATE_URL) !== false;
        }

        return (bool) preg_match('#^(/(?!/)|\#[A-Za-z0-9_-]+$|tel:\+?[0-9()-]{5,20}$)#i', $enlace);
    }
}
