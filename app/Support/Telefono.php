<?php

namespace App\Support;

use App\Models\User;

/**
 * Teléfonos de México: se guardan siempre como 10 dígitos ("9991234567"), igual que los que crea el chat.
 * Se aceptan espacios, guiones, paréntesis y la lada 52, +52 o 521 al escribirlos.
 * Así "999 123 4567" y "9991234567" son el mismo teléfono y no se puede duplicar una cuenta.
 */
class Telefono
{
    /** Los 10 dígitos del teléfono, o null si no tiene exactamente 10 (después de quitar la lada 52/521) */
    public static function normalizar(mixed $valor): ?string
    {
        if (!is_string($valor) && !is_int($valor)) {
            return null;
        }

        $digitos = (string) preg_replace('/\D+/', '', (string) $valor);

        if (strlen($digitos) === 13 && str_starts_with($digitos, '521')) {
            $digitos = substr($digitos, 3);
        } elseif (strlen($digitos) === 12 && str_starts_with($digitos, '52')) {
            $digitos = substr($digitos, 2);
        }

        return strlen($digitos) === 10 ? $digitos : null;
    }

    /** ¿Ya hay una cuenta con ese teléfono? Compara por los últimos 10 dígitos: hay teléfonos guardados con formato */
    public static function existe(string $telefono10, ?int $ignorarId = null): bool
    {
        return User::whereRaw(
            "SUBSTR(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefono, ' ', ''), '-', ''), '(', ''), ')', ''), '+', ''), -10) = ?",
            [$telefono10],
        )->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))->exists();
    }

    /**
     * Reglas de validación de un campo de teléfono: 10 dígitos y que no esté repetido.
     *
     * @param string $duplicado Mensaje cuando ya existe una cuenta con ese teléfono
     */
    public static function reglas(bool $obligatorio, string $duplicado, ?int $ignorarId = null): array
    {
        return [
            $obligatorio ? 'required' : 'nullable',
            'string',
            'max:20',
            function (string $atributo, mixed $valor, \Closure $fallar) use ($duplicado, $ignorarId) {
                $telefono = self::normalizar($valor);

                if ($telefono === null) {
                    $fallar('El teléfono debe tener 10 dígitos (puedes escribir espacios o guiones).');
                } elseif (self::existe($telefono, $ignorarId)) {
                    $fallar($duplicado);
                }
            },
        ];
    }
}
