<?php

namespace App\Services\Chat;

use Illuminate\Support\Str;

/**
 * Quita del FINAL de lo que responde el modelo las acotaciones entre paréntesis o corchetes
 * ("(Esperando respuesta del usuario)", "[Pausa]"…): son descripciones de su estado, no algo
 * que se le diga al paciente. Solo se quitan al final y solo si lo de adentro EMPIEZA con una
 * palabra de acotación; un paréntesis legítimo ("(10:00 AM)", "(frente al parque)") se queda,
 * esté donde esté, y también los que traen una acotación a mitad de la frase.
 *
 * Se aplica solo al texto del modelo, nunca a los resúmenes que escribe el servidor (van
 * atados a una firma exacta).
 */
class LimpiezaRespuesta
{
    /** Palabras con las que empieza una acotación (sin acentos ni mayúsculas) */
    private const PALABRAS = [
        'esperando', 'aguardando', 'espero', 'pausa', 'nota', 'pensando', 'escribiendo',
        'procesando', 'silencio', 'sistema', 'acotacion', 'el usuario', 'la usuaria',
    ];

    // Un paréntesis o corchete al final (sin otros adentro, en una sola línea), con a lo mucho
    // asteriscos, guiones bajos, puntos o espacios alrededor
    private const FINAL = '/[\s*_]*[(\[]\s*[*_]*\s*([^()\[\]\n]{1,200}?)\s*[*_]*\s*[)\]][\s*_.]*\z/u';

    public function limpiar(string $texto): string
    {
        $texto = trim($texto);

        while (preg_match(self::FINAL, $texto, $coincidencia) && $this->esAcotacion($coincidencia[1])) {
            $texto = trim((string) preg_replace(self::FINAL, '', $texto, 1));
        }

        return $texto;
    }

    private function esAcotacion(string $contenido): bool
    {
        $contenido = mb_strtolower(Str::ascii($contenido));

        foreach (self::PALABRAS as $palabra) {
            // La palabra completa al inicio: "esperando" sí, "Esperanza" no
            if (preg_match('/^' . preg_quote($palabra, '/') . '\b/u', $contenido)) {
                return true;
            }
        }

        return false;
    }
}
