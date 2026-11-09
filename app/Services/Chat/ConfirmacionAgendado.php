<?php

namespace App\Services\Chat;

/**
 * Confirmación explícita del paciente, verificada por el servidor (no basta pedírsela a la IA).
 *
 * La primera llamada a agendar_cita NO agenda: el servidor escribe el resumen y emite una
 * propuesta firmada (HMAC con APP_KEY) con los datos y el hash EXACTO de ese resumen. El
 * navegador la devuelve en la petición siguiente y solo se agenda si:
 *  - la firma es válida y no caducó,
 *  - el último mensaje del asistente en el historial es ese resumen (el "sí" responde a él),
 *  - la IA llama otra vez con exactamente los mismos datos, y
 *  - el último mensaje del visitante es una afirmación clara.
 */
class ConfirmacionAgendado
{
    /** Palabras que, solas o acompañadas de pocas más, cuentan como un "sí" */
    private const AFIRMATIVAS = [
        'si', 'sii', 'siii', 'sip', 'claro', 'ok', 'okey', 'okay', 'dale', 'vale', 'correcto',
        'confirmo', 'confirmado', 'perfecto', 'adelante', 'listo', 'exacto', 'afirmativo',
        'agendala', 'agendalo', 'agenda', 'acuerdo',
    ];

    /** Cualquiera de estas anula el "sí": el visitante duda o quiere cambiar algo */
    private const NEGATIVAS = [
        'no', 'nunca', 'nel', 'cambia', 'cambiar', 'cambio', 'cambialo', 'mejor', 'pero', 'espera',
        'esperame', 'otra', 'otro', 'otras', 'otros', 'tal', 'vez', 'duda', 'dudo', 'aun', 'todavia',
        'cancela', 'cancelar', 'antes', 'primero', 'solo', 'sino',
    ];

    public function emitir(array $datos, string $resumen): string
    {
        $carga = $this->base64(json_encode([
            'd' => $datos,
            'h' => $this->hash($resumen),
            't' => now()->timestamp,
        ], JSON_UNESCAPED_UNICODE));

        return $carga . '.' . $this->firmar($carga);
    }

    /**
     * Datos de la propuesta si el token es auténtico, vigente y corresponde al último mensaje
     * del asistente; null en cualquier otro caso.
     */
    public function leer(?string $token, ?string $ultimoAsistente): ?array
    {
        if (!is_string($token) || $ultimoAsistente === null || substr_count($token, '.') !== 1) {
            return null;
        }

        [$carga, $firma] = explode('.', $token);

        if (!hash_equals($this->firmar($carga), $firma)) {
            return null;
        }

        $contenido = json_decode((string) base64_decode(strtr($carga, '-_', '+/'), true), true);

        if (!is_array($contenido) || !is_array($contenido['d'] ?? null) || !is_int($contenido['t'] ?? null)) {
            return null;
        }

        $vigencia = (int) config('chat.agenda.propuesta_minutos') * 60;

        if (now()->timestamp - $contenido['t'] > $vigencia || $contenido['t'] > now()->timestamp + 60) {
            return null;
        }

        if (!hash_equals((string) ($contenido['h'] ?? ''), $this->hash($ultimoAsistente))) {
            return null;
        }

        return $contenido['d'];
    }

    public function coincide(?array $vigente, array $datos): bool
    {
        return $vigente !== null && json_encode($vigente) === json_encode($datos);
    }

    /** ¿El mensaje es un "sí" claro, sin dudas, cambios ni datos nuevos? */
    public function esAfirmativo(string $texto): bool
    {
        $texto = mb_strtolower(trim($texto));

        // Solo un gesto
        if (preg_match('/^(?:\s*(?:👍|✅|👌|🙌))+\s*$/u', $texto)) {
            return true;
        }

        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u']);
        $texto = trim((string) preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $texto));

        // Números: son datos nuevos ("sí, a las 11"), no un sí
        if ($texto === '' || preg_match('/\d/', $texto)) {
            return false;
        }

        $palabras = preg_split('/\s+/', $texto);

        if (count($palabras) > 8 || array_intersect($palabras, self::NEGATIVAS) !== []) {
            return false;
        }

        return array_intersect($palabras, self::AFIRMATIVAS) !== [];
    }

    private function hash(string $texto): string
    {
        return hash('sha256', trim($texto));
    }

    private function firmar(string $carga): string
    {
        return hash_hmac('sha256', 'chat-propuesta|' . $carga, (string) config('app.key'));
    }

    private function base64(string $valor): string
    {
        return rtrim(strtr(base64_encode($valor), '+/', '-_'), '=');
    }
}
