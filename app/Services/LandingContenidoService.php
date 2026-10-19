<?php

namespace App\Services;

use App\Models\LandingContenido;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Contenido editable de la landing page (imágenes y textos).
 *
 * Los campos, sus valores originales y sus reglas están en config/landing.php.
 * La tabla landing_contenidos guarda solo lo que se personalizó: si un campo no
 * tiene fila, se muestra el valor original, de modo que la landing no cambia
 * hasta que alguien edite algo. Restaurar un campo borra su fila (y su imagen
 * subida); los archivos originales de public/ nunca se tocan.
 */
class LandingContenidoService
{
    const CARPETA_IMAGENES = 'landing';

    private ?array $personalizados = null;

    protected $imagenes;

    public function __construct(ImagenService $imagenes)
    {
        $this->imagenes = $imagenes;
    }

    // ---------- Catálogo ----------

    public static function campos(): array
    {
        return config('landing.campos');
    }

    public static function secciones(): array
    {
        return config('landing.secciones');
    }

    public static function camposDeSeccion(string $seccion): array
    {
        return array_filter(self::campos(), fn ($def, $clave) => str_starts_with($clave, $seccion . '.'), ARRAY_FILTER_USE_BOTH);
    }

    public static function seccionesDePestana(string $pestana): array
    {
        return array_filter(self::secciones(), fn ($s) => $s['pestana'] === $pestana);
    }

    public static function definicion(string $clave): array
    {
        return self::campos()[$clave] ?? throw new \InvalidArgumentException("Campo de landing desconocido: $clave");
    }

    // Los puntos de la clave no sirven en nombres de formulario (Laravel los lee como anidación)
    public static function nombreDeCampo(string $clave): string
    {
        return str_replace('.', '__', $clave);
    }

    // ---------- Lectura ----------

    /** Valor actual: el personalizado si hay uno, si no el original. */
    public function valor(string $clave): string
    {
        $definicion = self::definicion($clave);
        $personalizado = $this->personalizados()[$clave] ?? null;

        return ($personalizado !== null && $personalizado !== '') ? $personalizado : (string) $definicion['defecto'];
    }

    public function texto(string $clave): string
    {
        return $this->valor($clave);
    }

    /** Título de hasta 2 líneas, escapado, con el mismo " <br> " que tenía la vista original. */
    public function titulo(string $clave): HtmlString
    {
        $lineas = array_map('e', explode("\n", $this->valor($clave)));

        return new HtmlString(implode(' <br> ', $lineas));
    }

    /** URL de la imagen: la subida si hay una válida; si no, la original de public/. */
    public function imagen(string $clave): string
    {
        $definicion = self::definicion($clave);
        $personalizada = $this->personalizados()[$clave] ?? null;

        return ($personalizada ? $this->imagenes->url($personalizada) : null) ?? asset($definicion['defecto']);
    }

    public function enlaceTelefono(string $clave = 'contacto.telefono'): string
    {
        return 'tel:+52' . $this->valor($clave);
    }

    public function enlaceWhatsapp(string $clave = 'contacto.whatsapp'): string
    {
        return 'https://wa.me/52' . $this->valor($clave);
    }

    public function esPersonalizado(string $clave): bool
    {
        return isset($this->personalizados()[$clave]);
    }

    public function seccionPersonalizada(string $seccion): bool
    {
        foreach (array_keys(self::camposDeSeccion($seccion)) as $clave) {
            if ($this->esPersonalizado($clave)) {
                return true;
            }
        }

        return false;
    }

    /** Ruta de la imagen subida (para el panel), o null si se usa la original. */
    public function rutaSubida(string $clave): ?string
    {
        return $this->personalizados()[$clave] ?? null;
    }

    /**
     * Lo personalizado, en una sola consulta por petición. Si la consulta falla
     * (tabla sin migrar, base caída) devuelve vacío: la landing se ve con sus
     * valores originales y no con un error 500.
     */
    private function personalizados(): array
    {
        if ($this->personalizados === null) {
            try {
                $this->personalizados = LandingContenido::pluck('valor', 'clave')->all();
            } catch (\Throwable $e) {
                Log::warning('Landing: no se pudo leer el contenido personalizado: ' . $e->getMessage());
                $this->personalizados = [];
            }
        }

        return $this->personalizados;
    }

    // ---------- Normalización y validación ----------

    /**
     * Deja el valor en su forma de guardado. null = "usar el original"
     * (campo vacío o igual al original).
     */
    public static function normalizar(string $clave, ?string $valor): ?string
    {
        $definicion = self::definicion($clave);

        if ($valor === null || trim($valor) === '') {
            return null;
        }

        $valor = match ($definicion['tipo']) {
            'texto', 'parrafo' => trim(preg_replace('/\s+/u', ' ', $valor)),
            'titulo' => implode("\n", array_values(array_filter(array_map(
                fn ($linea) => trim(preg_replace('/[ \t]+/u', ' ', $linea)),
                preg_split('/\R/u', $valor)
            ), fn ($linea) => $linea !== ''))),
            'telefono' => self::normalizarTelefono($valor),
            'mapa' => self::extraerMapa($valor),
            default => trim($valor),
        };

        return $valor === (string) $definicion['defecto'] ? null : $valor;
    }

    // Quita espacios, guiones, paréntesis y la lada 52 / 521 de México
    private static function normalizarTelefono(string $valor): string
    {
        $digitos = preg_replace('/\D+/', '', $valor);

        // Sin ningún dígito no es un teléfono: se deja tal cual para que la validación lo rechace
        if ($digitos === '') {
            return trim($valor);
        }

        if (strlen($digitos) === 13 && str_starts_with($digitos, '521')) {
            return substr($digitos, 3);
        }
        if (strlen($digitos) === 12 && str_starts_with($digitos, '52')) {
            return substr($digitos, 2);
        }

        return $digitos;
    }

    // Acepta la dirección sola o el código <iframe …> de Google Maps completo
    private static function extraerMapa(string $valor): string
    {
        if (stripos($valor, '<iframe') !== false && preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $valor, $coincidencia)) {
            return html_entity_decode(trim($coincidencia[1]), ENT_QUOTES);
        }

        return trim($valor);
    }

    public static function mapaValido(string $url): bool
    {
        return (bool) preg_match('#^https://(www\.google\.com/maps/embed[?/]|maps\.google\.com/maps\?)[^\s"\'<>\\\\]+$#', $url)
            && (str_contains($url, 'google.com/maps/embed') || str_contains($url, 'output=embed'));
    }

    public static function redValida(string $url, array $hosts): bool
    {
        if (!preg_match('#^https://[^\s"\'<>\\\\]+$#i', $url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        foreach ($hosts as $permitido) {
            if ($host === $permitido || str_ends_with($host, '.' . $permitido)) {
                return true;
            }
        }

        return false;
    }

    /** Reglas de validación de una sección, con los nombres que usa el formulario. */
    public static function reglas(string $seccion): array
    {
        $reglas = [];

        foreach (self::camposDeSeccion($seccion) as $clave => $def) {
            $nombre = self::nombreDeCampo($clave);
            $max = $def['max'] ?? 255;

            $reglas[$nombre] = match ($def['tipo']) {
                'imagen' => ImagenService::reglas(),
                'texto', 'parrafo' => ['nullable', 'string', "max:$max"],
                'titulo' => ['nullable', 'string', "max:$max", function ($atributo, $valor, $fallar) {
                    if (substr_count($valor, "\n") > 1) {
                        $fallar('El título puede tener como máximo 2 líneas.');
                    }
                }],
                'telefono' => ['nullable', 'regex:/^\d{10}$/'],
                'red' => ['nullable', 'max:255', function ($atributo, $valor, $fallar) use ($def) {
                    if (!self::redValida($valor, $def['hosts'])) {
                        $fallar('Escribe la dirección completa, que empiece con https:// y sea de ' . implode(' o ', $def['hosts']) . '.');
                    }
                }],
                'mapa' => ['nullable', 'max:2000', function ($atributo, $valor, $fallar) {
                    if (!self::mapaValido($valor)) {
                        $fallar('Pega el código para insertar de Google Maps, o una dirección que empiece con https://www.google.com/maps/embed.');
                    }
                }],
            };
        }

        return $reglas;
    }

    public static function mensajes(string $seccion): array
    {
        $mensajes = ImagenService::mensajes();
        $campos = self::camposDeSeccion($seccion);

        foreach ($campos as $clave => $def) {
            $nombre = self::nombreDeCampo($clave);
            $etiqueta = mb_strtolower($def['etiqueta']);

            if ($def['tipo'] === 'imagen') {
                foreach (ImagenService::mensajes() as $regla => $texto) {
                    $mensajes[str_replace('imagen.', "$nombre.", $regla)] = $texto;
                }
                continue;
            }

            $mensajes["$nombre.max"] = "El campo \"{$def['etiqueta']}\" es demasiado largo (máximo " . ($def['max'] ?? 255) . ' caracteres).';
            $mensajes["$nombre.regex"] = $def['tipo'] === 'telefono'
                ? "El campo \"{$def['etiqueta']}\" debe tener 10 dígitos."
                : "El campo \"{$def['etiqueta']}\" no es válido.";
        }

        return $mensajes;
    }

    // ---------- Escritura ----------

    /**
     * Guarda los campos de una sección.
     *
     * @param array<string, ?string>        $valores  clave => valor ya normalizado (null = volver al original)
     * @param array<string, UploadedFile>   $archivos clave => imagen nueva
     */
    public function guardar(array $valores, array $archivos = []): void
    {
        $rutasNuevas = [];
        $rutasABorrar = [];

        try {
            // Primero los archivos: si algo falla, no se toca la base
            foreach ($archivos as $clave => $archivo) {
                $rutasNuevas[$clave] = $this->imagenes->guardar($archivo, self::CARPETA_IMAGENES);
            }

            DB::transaction(function () use ($valores, $rutasNuevas, &$rutasABorrar) {
                foreach ($valores as $clave => $valor) {
                    $this->escribir($clave, $valor, $rutasABorrar);
                }
                foreach ($rutasNuevas as $clave => $ruta) {
                    $this->escribir($clave, $ruta, $rutasABorrar);
                }
            });
        } catch (\Throwable $e) {
            foreach ($rutasNuevas as $ruta) {
                $this->imagenes->eliminar($ruta);
            }
            throw $e;
        } finally {
            $this->personalizados = null;
        }

        // Las imágenes reemplazadas se borran hasta que lo nuevo ya quedó guardado
        foreach ($rutasABorrar as $ruta) {
            $this->imagenes->eliminar($ruta);
        }
    }

    // Guarda o borra la fila de un campo; anota la imagen anterior para borrarla después
    private function escribir(string $clave, ?string $valor, array &$rutasABorrar): void
    {
        $definicion = self::definicion($clave);
        $fila = LandingContenido::where('clave', $clave)->first();

        if ($fila && $definicion['tipo'] === 'imagen' && $fila->valor !== $valor) {
            $rutasABorrar[] = $fila->valor;
        }

        if ($valor === null) {
            $fila?->delete();

            return;
        }

        $fila ? $fila->update(['valor' => $valor]) : LandingContenido::create(['clave' => $clave, 'valor' => $valor]);
    }

    public function restaurarCampo(string $clave): void
    {
        $rutasABorrar = [];

        DB::transaction(function () use ($clave, &$rutasABorrar) {
            $this->escribir($clave, null, $rutasABorrar);
        });
        $this->personalizados = null;

        foreach ($rutasABorrar as $ruta) {
            $this->imagenes->eliminar($ruta);
        }
    }

    public function restaurarSeccion(string $seccion): void
    {
        $rutasABorrar = [];

        DB::transaction(function () use ($seccion, &$rutasABorrar) {
            foreach (array_keys(self::camposDeSeccion($seccion)) as $clave) {
                $this->escribir($clave, null, $rutasABorrar);
            }
        });
        $this->personalizados = null;

        foreach ($rutasABorrar as $ruta) {
            $this->imagenes->eliminar($ruta);
        }
    }
}
