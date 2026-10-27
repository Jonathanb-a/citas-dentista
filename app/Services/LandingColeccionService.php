<?php

namespace App\Services;

use App\Models\LandingItem;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Listas administrables de la landing (la galería y las opiniones de pacientes). Ver
 * "colecciones" en config/landing.php.
 *
 * Cada lista define si sus elementos llevan imagen ("imagenes", por defecto sí) y sus
 * campos: "texto" (por defecto, texto plano en una línea), "calificacion" (entero de 1 a 5)
 * o "casilla" (sí/no; con "obligatoria" hay que marcarla para guardar).
 *
 * Mientras una lista no tenga filas en landing_items se muestran sus elementos originales,
 * de modo que la landing no cambia hasta que alguien edite algo. El primer cambio guarda
 * los originales como filas (sin imagen propia) y ahí empieza a mandar la base de datos.
 * "Restaurar" borra las filas y las imágenes subidas: vuelven los originales.
 *
 * Un elemento se identifica con su "ref": el id numérico de la fila o, mientras la lista
 * no se haya tocado, la clave del original (galeria-1…).
 *
 * Las reglas de negocio (mínimo y máximo) lanzan \DomainException con un mensaje para
 * mostrar a la doctora.
 */
class LandingColeccionService
{
    const CARPETA_IMAGENES = 'landing';

    private array $memo = [];

    protected $imagenes;

    public function __construct(ImagenService $imagenes)
    {
        $this->imagenes = $imagenes;
    }

    // ---------- Catálogo ----------

    public static function definicion(string $coleccion): array
    {
        return config("landing.colecciones.$coleccion")
            ?? throw new \InvalidArgumentException("Lista de landing desconocida: $coleccion");
    }

    public static function existe(string $coleccion): bool
    {
        return config("landing.colecciones.$coleccion") !== null;
    }

    public static function usaImagenes(string $coleccion): bool
    {
        return self::definicion($coleccion)['imagenes'] ?? true;
    }

    private static function tipoDeCampo(array $definicionCampo): string
    {
        return $definicionCampo['tipo'] ?? 'texto';
    }

    // Valor de un campo con su tipo: texto (string), calificacion (int) o casilla (bool)
    private static function valorDeCampo(array $definicionCampo, mixed $valor): string|int|bool
    {
        return match (self::tipoDeCampo($definicionCampo)) {
            'calificacion' => (int) $valor,
            'casilla' => filter_var($valor, FILTER_VALIDATE_BOOLEAN),
            default => self::normalizarTexto(is_scalar($valor) ? (string) $valor : ''),
        };
    }

    // ---------- Lectura ----------

    /**
     * Elementos en el orden en que se muestran. Cada uno:
     * ref, src (URL de la imagen), imagen_propia (ruta subida o null), original (clave o null),
     * es_original (aún es el original sin tocar), y los campos de texto (titulo, subtitulo…).
     *
     * Si la consulta falla (tabla sin migrar, base caída) devuelve los originales: la
     * landing se ve como siempre y no con un error 500.
     */
    public function items(string $coleccion): array
    {
        return $this->memo[$coleccion] ??= $this->cargar($coleccion);
    }

    public function personalizada(string $coleccion): bool
    {
        return $this->filas($coleccion) !== [];
    }

    private function cargar(string $coleccion): array
    {
        $filas = $this->filas($coleccion);

        if ($filas === []) {
            return $this->itemsOriginales($coleccion);
        }

        return array_map(fn (LandingItem $fila) => $this->presentar($coleccion, $fila), $filas);
    }

    /** @return LandingItem[] */
    private function filas(string $coleccion): array
    {
        try {
            return LandingItem::where('coleccion', $coleccion)->orderBy('orden')->orderBy('id')->get()->all();
        } catch (\Throwable $e) {
            Log::warning("Landing: no se pudo leer la lista $coleccion: " . $e->getMessage());

            return [];
        }
    }

    private function itemsOriginales(string $coleccion): array
    {
        $definicion = self::definicion($coleccion);
        $items = [];

        foreach ($definicion['originales'] as $clave => $original) {
            $item = [
                'ref' => $clave,
                'src' => asset($original['imagen']),
                'imagen_propia' => null,
                'original' => $clave,
                'es_original' => true,
            ];

            foreach ($definicion['campos'] as $campo => $definicionCampo) {
                $item[$campo] = self::valorDeCampo($definicionCampo, $original[$campo] ?? '');
            }

            $items[] = $item;
        }

        return $items;
    }

    private function presentar(string $coleccion, LandingItem $fila): array
    {
        $definicion = self::definicion($coleccion);
        $original = $fila->original ? ($definicion['originales'][$fila->original] ?? null) : null;

        $src = ($fila->imagen ? $this->imagenes->url($fila->imagen) : null)
            ?? ($original ? asset($original['imagen']) : '');

        $item = [
            'ref' => (string) $fila->id,
            'src' => $src,
            'imagen_propia' => $fila->imagen,
            'original' => $fila->original,
            'es_original' => false,
        ];

        foreach ($definicion['campos'] as $campo => $definicionCampo) {
            $item[$campo] = self::valorDeCampo($definicionCampo, $fila->datos[$campo] ?? '');
        }

        return $item;
    }

    // ---------- Reglas de validación ----------

    public static function reglas(string $coleccion, bool $imagenObligatoria): array
    {
        $reglas = self::usaImagenes($coleccion) ? ['imagen' => ImagenService::reglas(null, $imagenObligatoria)] : [];

        foreach (self::definicion($coleccion)['campos'] as $campo => $definicion) {
            $reglas[$campo] = match (self::tipoDeCampo($definicion)) {
                'calificacion' => ['required', 'integer', 'between:1,5'],
                'casilla' => ($definicion['obligatoria'] ?? false) ? ['accepted'] : ['nullable', 'boolean'],
                default => [($definicion['requerido'] ?? false) ? 'required' : 'nullable', 'string', 'max:' . $definicion['max']],
            };
        }

        return $reglas;
    }

    public static function mensajes(string $coleccion): array
    {
        $mensajes = self::usaImagenes($coleccion) ? ImagenService::mensajes() + ['imagen.required' => 'Elige una imagen.'] : [];

        foreach (self::definicion($coleccion)['campos'] as $campo => $definicion) {
            $etiqueta = $definicion['etiqueta'];

            match (self::tipoDeCampo($definicion)) {
                'calificacion' => $mensajes += [
                    "$campo.required" => "Elige la calificación (de 1 a 5).",
                    "$campo.integer" => "La calificación debe ser un número de 1 a 5.",
                    "$campo.between" => "La calificación debe ser de 1 a 5.",
                ],
                'casilla' => $mensajes += ["$campo.accepted" => "Marca la casilla \"$etiqueta\" para poder guardar."],
                default => $mensajes += [
                    "$campo.required" => "Escribe el campo \"$etiqueta\".",
                    "$campo.max" => "El campo \"$etiqueta\" es demasiado largo (máximo {$definicion['max']} caracteres).",
                ],
            };
        }

        return $mensajes;
    }

    /** Texto plano en una línea; vacío = sin texto. */
    public static function normalizarTexto(?string $texto): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $texto));
    }

    // ---------- Escritura ----------

    /** Agrega un elemento al final. $datos: los campos; $imagen solo en listas con imágenes. */
    public function agregar(string $coleccion, ?UploadedFile $imagen, array $datos): LandingItem
    {
        $definicion = self::definicion($coleccion);

        if (self::usaImagenes($coleccion) && $imagen === null) {
            throw new \InvalidArgumentException("La lista $coleccion necesita una imagen.");
        }

        $ruta = self::usaImagenes($coleccion) ? $this->imagenes->guardar($imagen, self::CARPETA_IMAGENES) : null;

        try {
            return DB::transaction(function () use ($coleccion, $definicion, $ruta, $datos) {
                $this->materializar($coleccion);

                if (LandingItem::where('coleccion', $coleccion)->count() >= $definicion['maximo']) {
                    throw new \DomainException(str_replace(':maximo', $definicion['maximo'], $definicion['textos']['maximo']));
                }

                return LandingItem::create([
                    'coleccion' => $coleccion,
                    'orden' => ((int) LandingItem::where('coleccion', $coleccion)->max('orden')) + 1,
                    'original' => null,
                    'imagen' => $ruta,
                    'datos' => $this->soloCampos($coleccion, $datos),
                ]);
            });
        } catch (\Throwable $e) {
            $this->imagenes->eliminar($ruta); // no dejar la imagen huérfana
            throw $e;
        } finally {
            unset($this->memo[$coleccion]);
        }
    }

    /** Cambia los textos y, si llega una imagen, la reemplaza (la anterior se borra). */
    public function actualizar(string $coleccion, string $ref, array $datos, ?UploadedFile $imagen = null): void
    {
        $rutaNueva = ($imagen && self::usaImagenes($coleccion)) ? $this->imagenes->guardar($imagen, self::CARPETA_IMAGENES) : null;
        $rutaABorrar = null;

        try {
            DB::transaction(function () use ($coleccion, $ref, $datos, $rutaNueva, &$rutaABorrar) {
                $fila = $this->fila($coleccion, $ref);
                $cambios = ['datos' => $this->soloCampos($coleccion, $datos)];

                if ($rutaNueva) {
                    $rutaABorrar = $fila->imagen;
                    $cambios['imagen'] = $rutaNueva;
                }

                $fila->update($cambios);
            });
        } catch (\Throwable $e) {
            $this->imagenes->eliminar($rutaNueva);
            throw $e;
        } finally {
            unset($this->memo[$coleccion]);
        }

        $this->imagenes->eliminar($rutaABorrar);
    }

    /** Quita el elemento y borra su imagen subida. Siempre queda al menos el mínimo. */
    public function eliminar(string $coleccion, string $ref): void
    {
        $definicion = self::definicion($coleccion);
        $ruta = null;

        try {
            DB::transaction(function () use ($coleccion, $ref, $definicion, &$ruta) {
                $fila = $this->fila($coleccion, $ref);

                if (LandingItem::where('coleccion', $coleccion)->count() <= $definicion['minimo']) {
                    throw new \DomainException($definicion['textos']['minimo']);
                }

                $ruta = $fila->imagen;
                $fila->delete();
                $this->renumerar($coleccion);
            });
        } finally {
            unset($this->memo[$coleccion]);
        }

        $this->imagenes->eliminar($ruta);
    }

    /** Sube (-1) o baja (+1) una posición. */
    public function mover(string $coleccion, string $ref, int $direccion): void
    {
        try {
            DB::transaction(function () use ($coleccion, $ref, $direccion) {
                $fila = $this->fila($coleccion, $ref);
                $lista = LandingItem::where('coleccion', $coleccion)->orderBy('orden')->orderBy('id')->lockForUpdate()->get()->all();

                $actual = array_search($fila->id, array_map(fn ($f) => $f->id, $lista), true);
                $destino = $actual + $direccion;

                if (isset($lista[$destino])) {
                    [$lista[$actual], $lista[$destino]] = [$lista[$destino], $lista[$actual]];
                }

                $this->guardarOrden($lista);
            });
        } finally {
            unset($this->memo[$coleccion]);
        }
    }

    /** Un elemento que viene de un original vuelve a usar la imagen original (la subida se borra). */
    public function restaurarImagen(string $coleccion, string $ref): void
    {
        $ruta = null;

        try {
            DB::transaction(function () use ($coleccion, $ref, &$ruta) {
                $fila = $this->fila($coleccion, $ref);

                abort_if($fila->original === null, 404);

                $ruta = $fila->imagen;
                $fila->update(['imagen' => null]);
            });
        } finally {
            unset($this->memo[$coleccion]);
        }

        $this->imagenes->eliminar($ruta);
    }

    /** Vuelve a los originales: borra todas las filas y las imágenes subidas. */
    public function restaurar(string $coleccion): void
    {
        $rutas = [];

        try {
            DB::transaction(function () use ($coleccion, &$rutas) {
                $filas = LandingItem::where('coleccion', $coleccion)->get();
                $rutas = $filas->pluck('imagen')->filter()->all();
                LandingItem::where('coleccion', $coleccion)->delete();
            });
        } finally {
            unset($this->memo[$coleccion]);
        }

        foreach ($rutas as $ruta) {
            $this->imagenes->eliminar($ruta);
        }
    }

    // ---------- Internos ----------

    // Guarda los originales como filas la primera vez que se modifica la lista
    private function materializar(string $coleccion): void
    {
        if (LandingItem::where('coleccion', $coleccion)->exists()) {
            return;
        }

        $orden = 0;

        foreach (self::definicion($coleccion)['originales'] as $clave => $original) {
            try {
                LandingItem::create([
                    'coleccion' => $coleccion,
                    'orden' => ++$orden,
                    'original' => $clave,
                    'imagen' => null,
                    'datos' => $this->soloCampos($coleccion, $original),
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // Otra petición ya los guardó al mismo tiempo: no hay nada más que hacer
            }
        }
    }

    // Busca por id numérico o, si la lista aún no se tocó, por clave del original (y la materializa)
    private function fila(string $coleccion, string $ref): LandingItem
    {
        $this->materializar($coleccion);

        $consulta = LandingItem::where('coleccion', $coleccion);

        return (ctype_digit($ref) ? $consulta->where('id', (int) $ref) : $consulta->where('original', $ref))->firstOrFail();
    }

    private function soloCampos(string $coleccion, array $datos): array
    {
        $campos = [];

        foreach (self::definicion($coleccion)['campos'] as $campo => $definicionCampo) {
            $campos[$campo] = self::valorDeCampo($definicionCampo, $datos[$campo] ?? '');
        }

        return $campos;
    }

    private function renumerar(string $coleccion): void
    {
        $this->guardarOrden(LandingItem::where('coleccion', $coleccion)->orderBy('orden')->orderBy('id')->get()->all());
    }

    /** @param LandingItem[] $lista */
    private function guardarOrden(array $lista): void
    {
        foreach ($lista as $posicion => $fila) {
            if ($fila->orden !== $posicion + 1) {
                $fila->update(['orden' => $posicion + 1]);
            }
        }
    }
}
