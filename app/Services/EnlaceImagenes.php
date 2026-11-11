<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Crea (o corrige) el enlace simbólico public/imagenes → IMAGENES_ROOT al arrancar, para no depender
 * del Start Command de Railway (los archivos de public/ se pierden en cada deploy; el Volume no).
 *
 * - Solo si hay un Volume: IMAGENES_ROOT definido y distinto de la ruta por defecto del proyecto, y
 *   el disco de imágenes es el local `imagenes` (no s3)
 * - Solo en peticiones web: no corre en pruebas ni en consola (migrate, schedule, storage:link…)
 * - Una sola vez por proceso; si todo está bien, el costo es un readlink
 * - Nunca rompe la app: todo en try/catch y el log lleva solo la clase del error y el nombre de la
 *   función que falló, sin rutas. Si no se puede crear, `php artisan storage:link` es el respaldo manual
 * - Si public/imagenes es una carpeta o un archivo REAL no la toca (podría tener imágenes): solo avisa
 */
class EnlaceImagenes
{
    const LISTO = 'listo';                  // el enlace ya apuntaba al destino
    const CREADO = 'creado';
    const CORREGIDO = 'corregido';          // apuntaba a otro lugar
    const NO_ES_ENLACE = 'no_es_enlace';    // carpeta o archivo real: no se toca
    const FALLO = 'fallo';

    private static bool $hecho = false;

    /** Para las pruebas: vuelve a permitir la comprobación en el mismo proceso */
    public static function reiniciar(): void
    {
        self::$hecho = false;
    }

    /** Lo llama AppServiceProvider::boot(). No lanza excepciones */
    public function asegurar(): void
    {
        try {
            if (self::$hecho || !$this->debeCorrer()) {
                return;
            }

            self::$hecho = true;
            $this->sincronizar(public_path('imagenes'), (string) config('filesystems.disks.imagenes.root'));
        } catch (\Throwable $e) {
            Log::warning('Imágenes: no se pudo preparar el enlace public/imagenes (' . class_basename($e) . '). Usa php artisan storage:link.');
        }
    }

    /** Solo en peticiones web (ni pruebas ni consola) y con un Volume configurado */
    protected function debeCorrer(): bool
    {
        return !app()->runningUnitTests() && !app()->runningInConsole() && $this->hayVolume();
    }

    /** IMAGENES_ROOT definido y distinto de la ruta por defecto, con el disco local de imágenes (no s3) */
    protected function hayVolume(): bool
    {
        if (config('imagenes.disk') !== 'imagenes') {
            return false;
        }

        $raiz = $this->normalizar((string) config('filesystems.disks.imagenes.root'));

        return $raiz !== '' && $raiz !== $this->normalizar((string) config('filesystems.imagenes_ruta_defecto'));
    }

    /** Deja $enlace apuntando a $destino. Devuelve una de las constantes; nunca lanza excepciones */
    public function sincronizar(string $enlace, string $destino): string
    {
        try {
            $destino = rtrim($destino, '/\\');

            if (!is_dir($destino) && !@mkdir($destino, 0775, true) && !is_dir($destino)) {
                return $this->fallo('mkdir');
            }

            if (is_link($enlace)) {
                if ($this->normalizar((string) readlink($enlace)) === $this->normalizar($destino)) {
                    return self::LISTO;
                }

                if (!@unlink($enlace)) {
                    return $this->fallo('unlink');
                }

                return $this->enlazar($destino, $enlace) ? self::CORREGIDO : $this->fallo('symlink');
            }

            if (file_exists($enlace)) {
                Log::warning('Imágenes: public/imagenes existe y no es un enlace; no se toca. Muévela o bórrala y usa php artisan storage:link.');

                return self::NO_ES_ENLACE;
            }

            return $this->enlazar($destino, $enlace) ? self::CREADO : $this->fallo('symlink');
        } catch (\Throwable $e) {
            return $this->fallo(class_basename($e));
        }
    }

    // Carrera entre procesos: si otro creó el enlace entre la comprobación y aquí, vale si apunta bien
    private function enlazar(string $destino, string $enlace): bool
    {
        if (@symlink($destino, $enlace)) {
            return true;
        }

        return is_link($enlace) && $this->normalizar((string) readlink($enlace)) === $this->normalizar($destino);
    }

    private function fallo(string $funcion): string
    {
        Log::warning("Imágenes: no se pudo crear el enlace public/imagenes (falló {$funcion}). Revisa los permisos o usa php artisan storage:link.");

        return self::FALLO;
    }

    private function normalizar(string $ruta): string
    {
        return rtrim(str_replace('\\', '/', $ruta), '/');
    }
}
