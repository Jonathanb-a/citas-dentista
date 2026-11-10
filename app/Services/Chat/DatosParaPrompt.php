<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Los textos que la clínica escribe (anuncios, nombres y descripciones de servicios) entran al
 * prompt como DATOS, nunca como órdenes. Aquí se limpian, se recortan y, si parecen un intento
 * de instrucción ("ignora las instrucciones anteriores…"), se descartan COMPLETOS (quien llama
 * omite el elemento): es más seguro que dejar el resto de la frase. Solo se registra su id.
 */
class DatosParaPrompt
{
    /**
     * Texto de una sola línea, sin invisibles, direcciones web ni marcas de formato o de
     * marcadores ({{ }}, < >, [ ], ` y |), y recortado a $max caracteres con "…" (en palabra completa).
     */
    public function limpiar(?string $texto, int $max): string
    {
        $limpio = (string) TextoPlano::limpiar((string) $texto);
        $limpio = (string) preg_replace('~(?:https?://|www\.)\S+~iu', '', $limpio);
        $limpio = (string) preg_replace('/[`<>{}\[\]|\\\\]/u', '', $limpio);
        $limpio = (string) preg_replace('/\s+/u', ' ', $limpio);
        $limpio = trim((string) preg_replace('/^[\s#>*_\-=~•]+/u', '', $limpio));

        return $this->recortar($limpio, $max);
    }

    /** ¿Parece una orden dirigida al modelo? Se revisa el texto completo, antes de recortarlo */
    public function pareceInstruccion(?string $texto): bool
    {
        $crudo = (string) TextoPlano::limpiar((string) $texto);

        if ($crudo === '') {
            return false;
        }

        // Marcadores de rol o de formato de plantillas de chat
        if (preg_match('/<\||\|>|\[\/?inst\]|<<\s*sys|^\s*#{2,}|^\s*(system|assistant|developer|sistema|asistente)\s*:/imu', $crudo)) {
            return true;
        }

        $normal = mb_strtolower(Str::ascii($crudo));
        $normal = trim((string) preg_replace('/[^a-z0-9_]+/', ' ', $normal));

        foreach (self::PATRONES as $patron) {
            if (preg_match($patron, $normal)) {
                return true;
            }
        }

        // Letras separadas o pegadas para esquivar el filtro ("i g n o r a  t u s  r e g l a s")
        $pegado = (string) preg_replace('/[^a-z]/', '', $normal);
        foreach (self::PEGADAS as $frase) {
            if (str_contains($pegado, $frase)) {
                return true;
            }
        }

        return false;
    }

    /** Avisa en el log, UNA vez por hora por elemento, que algo se omitió del prompt (solo el id, nunca el texto) */
    public function registrarOmitido(string $tipo, int $id): void
    {
        try {
            if (!Cache::add("chat:omitido:$tipo:$id", 1, now()->addHour())) {
                return;
            }
        } catch (\Throwable $e) {
            // Sin caché se registra cada vez: mejor ruido que silencio
        }

        Log::warning("Chat: se omitió del prompt $tipo $id porque su texto parece una instrucción");
    }

    private function recortar(string $texto, int $max): string
    {
        if ($max < 2 || mb_strlen($texto) <= $max) {
            return $texto;
        }

        $corte = mb_substr($texto, 0, $max - 1);
        $espacio = mb_strrpos($corte, ' ');

        // En la última palabra completa, si hay una razonablemente cerca del final
        if ($espacio !== false && $espacio >= (int) ($max * 0.5)) {
            $corte = mb_substr($corte, 0, $espacio);
        }

        return rtrim($corte, " ,;:.-") . '…';
    }

    // Sobre texto sin acentos, en minúsculas y con puntuación convertida en espacios
    private const PATRONES = [
        '/\b(ignora|ignorar|ignore|olvida|olvidar|omite|descarta|desobedece|deja de seguir|no sigas|no obedezcas)\b.{0,40}\b(instruccion|instrucciones|regla|reglas|indicacion|indicaciones|prompt|anterior|anteriores|lo anterior|lo de antes)\b/',
        '/\b(ignore|disregard|forget|override|bypass)\b.{0,40}\b(instruction|instructions|rules|prompt|previous|above|everything)\b/',
        '/\b(a partir de ahora|desde ahora|de ahora en adelante|from now on)\b.{0,40}\b(eres|debes|responde|responderas|ignora|obedece|actua|haz|di que|dile)\b/',
        '/\b(eres ahora|ahora eres|you are now|actua como|actuas como|actuaras como|act as|finge ser|finge que|haz de cuenta|pretend to be)\b/',
        '/\b(nuevas instrucciones|nueva instruccion|nuevas reglas|nueva regla|nuevas ordenes|new instructions|new rules)\b/',
        '/\b(system prompt|prompt del sistema|instrucciones del sistema|developer mode|modo desarrollador|jailbreak)\b/',
        '/\b(revela|muestra|repite|imprime|comparte|dime)\b.{0,30}\b(tus instrucciones|tu prompt|tus reglas|las instrucciones|el prompt)\b/',
        '/\b(agenda|agendar|reserva|reservar)\b.{0,25}\bsin (preguntar|confirmar|pedir confirmacion|consultar)\b/',
        '/\b(sin pedir confirmacion|confirma automaticamente|no pidas confirmacion)\b/',
        '/\b(agendar_cita|ver_horarios_libres|listar_servicios)\b/',
    ];

    // Frases fuertes buscadas sin espacios ni signos
    private const PEGADAS = [
        'ignoratusinstrucciones', 'ignoralasinstrucciones', 'ignoratusreglas', 'ignoralasreglas',
        'olvidatusinstrucciones', 'olvidalasinstrucciones', 'ignoreallinstructions', 'ignorepreviousinstructions',
        'ignoreyourinstructions', 'systemprompt', 'agendasinpreguntar',
    ];
}
