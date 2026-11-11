<?php

namespace App\Services;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Support\Facades\Log;
use Resend\Client;
use Resend\Transporters\HttpTransporter;
use Resend\ValueObjects\ApiKey;
use Resend\ValueObjects\Transporter\BaseUri;
use Resend\ValueObjects\Transporter\Headers;

/**
 * Transporte de correo "resend" de Laravel, pero con el cliente HTTP con tiempos límite
 * (config mail.mailers.resend: timeout y connect_timeout). El que trae el paquete no tiene
 * ninguno. La clave sale de config('services.resend.key'), nunca de env().
 *
 * Si falta la clave deja un aviso en el log (sin datos) y sigue: el envío fallará con una
 * excepción que ya atrapan los puntos de envío, así que nada rompe la petición del usuario.
 */
class CorreoResend
{
    private bool $avisoDado = false;

    public function transporte(array $config): ResendTransport
    {
        $clave = (string) ($config['key'] ?? config('services.resend.key'));

        if ($clave === '' && !$this->avisoDado) {
            $this->avisoDado = true;
            Log::warning('Correo: MAIL_MAILER=resend pero falta RESEND_API_KEY; los correos no saldrán.');
        }

        return new ResendTransport($this->cliente($clave, $config));
    }

    public function cliente(string $clave, array $config = []): Client
    {
        $http = new GuzzleClient([
            'timeout' => (float) ($config['timeout'] ?? config('mail.mailers.resend.timeout', 5)),
            'connect_timeout' => (float) ($config['connect_timeout'] ?? config('mail.mailers.resend.connect_timeout', 3)),
        ]);

        return new Client(new HttpTransporter(
            $http,
            BaseUri::from(getenv('RESEND_BASE_URL') ?: 'api.resend.com'),
            Headers::withAuthorization(ApiKey::from($clave)),
        ));
    }
}
