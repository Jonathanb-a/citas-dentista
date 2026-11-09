<?php

namespace App\Services\Chat;

/**
 * Limpieza de texto compartida: lo que escribe el visitante (ChatRequest) y lo que el servidor
 * le muestra (nombres de servicios). Los dos deben dar el mismo resultado, porque el texto
 * que el servidor firma tiene que coincidir con el que vuelve del navegador.
 */
class TextoPlano
{
    /**
     * UTF-8 válido, saltos de línea normalizados y sin caracteres de control ni de formato
     * (invisibles, bidireccionales, "tags" Unicode): sirven para ocultar instrucciones.
     * Devuelve null si no es UTF-8 válido.
     */
    public static function limpiar(mixed $valor): mixed
    {
        if (!is_string($valor)) {
            return $valor;
        }

        if (!mb_check_encoding($valor, 'UTF-8')) {
            return null;
        }

        $valor = preg_replace('/\r\n?/', "\n", $valor);
        $valor = preg_replace('/(?!\n)[\p{Cc}\p{Cf}\p{Co}\p{Cn}]/u', '', (string) $valor);

        return trim((string) $valor);
    }
}
