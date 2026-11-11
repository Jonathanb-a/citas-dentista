<?php

namespace App\Services;

use App\Models\Testimonio;
use App\Models\User;
use App\Services\Chat\DatosParaPrompt;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reglas de los testimonios de pacientes. Es el ÚNICO lugar que decide quién puede opinar
 * (la política y los controladores lo usan) y qué se publica en la landing.
 *
 * Nunca registra el contenido ni el nombre: solo ids y la clase de la excepción.
 */
class TestimonioService
{
    public function __construct(private DatosParaPrompt $datos) {}

    // ---------- Elegibilidad ----------

    /** Paciente activo, con cuenta usable (no del chat) y al menos una cita ATENDIDA */
    public function puedeOpinar(User $usuario): bool
    {
        return $usuario->role === 'paciente'
            && $usuario->activo
            && !$usuario->esDeChat()
            && $usuario->citas()->where('status', 'atendida')->exists();
    }

    /** Para explicarle al paciente por qué no ve el formulario */
    public function motivoSinAcceso(User $usuario): ?string
    {
        if ($this->puedeOpinar($usuario)) {
            return null;
        }

        return 'Podrás dejar tu opinión después de tu primera cita atendida.';
    }

    // ---------- Texto ----------

    /** Sin invisibles, enlaces ni marcado, en una sola línea. La longitud se valida DESPUÉS de esto */
    public function limpiarTexto(mixed $texto): string
    {
        return is_string($texto) ? $this->datos->limpiar($texto, 100000) : '';
    }

    // ---------- Paciente ----------

    /** Crea el testimonio (pendiente). Null si el paciente ya tenía uno (índice único, también ante carreras) */
    public function crear(User $paciente, int $calificacion, string $texto): ?Testimonio
    {
        try {
            return Testimonio::create([
                'paciente_id' => $paciente->id,
                'calificacion' => $calificacion,
                'texto' => $texto,
                'autorizado' => true,
            ]);
        } catch (QueryException $e) {
            if (Testimonio::where('paciente_id', $paciente->id)->exists()) {
                return null;
            }

            throw $e;
        }
    }

    /** Editar siempre devuelve el testimonio a revisión: la doctora vuelve a leerlo y deja de estar destacado */
    public function editar(Testimonio $testimonio, int $calificacion, string $texto): Testimonio
    {
        $testimonio->fill(['calificacion' => $calificacion, 'texto' => $texto, 'autorizado' => true]);
        $testimonio->estado = Testimonio::PENDIENTE;
        $testimonio->destacado = false;
        $testimonio->aprobado_en = null;
        $testimonio->save();

        return $testimonio;
    }

    // ---------- Doctora ----------

    public function aprobar(Testimonio $testimonio): bool
    {
        if ($testimonio->estado !== Testimonio::PENDIENTE) {
            return false;
        }

        $testimonio->estado = Testimonio::APROBADO;
        $testimonio->aprobado_en = now();
        $testimonio->save();

        return true;
    }

    /** Oculta un testimonio pendiente o aprobado; deja de estar destacado */
    public function ocultar(Testimonio $testimonio): bool
    {
        if ($testimonio->estado === Testimonio::OCULTO) {
            return false;
        }

        $testimonio->estado = Testimonio::OCULTO;
        $testimonio->destacado = false;
        $testimonio->aprobado_en = null;
        $testimonio->save();

        return true;
    }

    /** Un testimonio oculto vuelve a revisión (no se publica directo) */
    public function restaurar(Testimonio $testimonio): bool
    {
        if ($testimonio->estado !== Testimonio::OCULTO) {
            return false;
        }

        $testimonio->estado = Testimonio::PENDIENTE;
        $testimonio->save();

        return true;
    }

    /**
     * Destaca o quita lo destacado. Solo aprobados, y hasta config('testimonios.max_destacados').
     * Devuelve null si se pudo, o el motivo ('no_aprobado' | 'maximo')
     */
    public function destacar(Testimonio $testimonio, bool $destacado): ?string
    {
        if ($destacado === false) {
            // 'destacado' no es asignable en masa: se fija directo
            $testimonio->destacado = false;
            $testimonio->save();

            return null;
        }

        if ($testimonio->estado !== Testimonio::APROBADO) {
            return 'no_aprobado';
        }

        // Contar y guardar juntos para que dos clics a la vez no pasen del máximo
        return DB::transaction(function () use ($testimonio) {
            $actuales = Testimonio::where('destacado', true)->where('id', '!=', $testimonio->id)->lockForUpdate()->count();

            if ($actuales >= (int) config('testimonios.max_destacados')) {
                return 'maximo';
            }

            $testimonio->destacado = true;
            $testimonio->save();

            return null;
        });
    }

    // ---------- Landing ----------

    /**
     * Lo que publica la landing: solo APROBADOS, destacados primero y luego los más recientes.
     * El promedio cuenta TODOS los aprobados (no solo los que caben) y solo se da con el mínimo
     * configurado. Si algo falla, nada (la landing se ve sin testimonios de pacientes, no un 500).
     *
     * @return array{items: array<int, array{nombre: string, texto: string, calificacion: int}>, promedio: ?float, total: int}
     */
    public function paraLanding(): array
    {
        $vacio = ['items' => [], 'promedio' => null, 'total' => 0];

        try {
            $total = Testimonio::aprobados()->count();

            if ($total === 0) {
                return $vacio;
            }

            $items = Testimonio::aprobados()
                ->with('paciente:id,name,apellidos')
                ->orderByDesc('destacado')
                ->orderByDesc('aprobado_en')
                ->orderByDesc('id')
                ->limit((int) config('testimonios.max_landing'))
                ->get()
                ->map(fn (Testimonio $t) => [
                    'nombre' => $t->nombrePublico(),
                    'texto' => $t->texto,
                    'calificacion' => $t->calificacion,
                    'destacado' => $t->destacado,
                ])
                ->all();

            $promedio = $total >= (int) config('testimonios.promedio_minimo')
                ? round((float) Testimonio::aprobados()->avg('calificacion'), 1)
                : null;

            return ['items' => $items, 'promedio' => $promedio, 'total' => $total];
        } catch (\Throwable $e) {
            Log::warning('Landing: no se pudieron cargar los testimonios de pacientes (' . class_basename($e) . ')');

            return $vacio;
        }
    }
}
