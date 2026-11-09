<?php

namespace App\Console\Commands;

use App\Services\IA\Datos\MensajeIA;
use App\Services\IA\ProveedorIA;
use Illuminate\Console\Command;

/**
 * Comando temporal: prueba el servicio de IA con la key real del .env.
 * Borrar (junto con su prueba) cuando el chat ya esté en uso.
 */
class IaProbar extends Command
{
    protected $signature = 'ia:probar';

    protected $description = 'Manda un mensaje de prueba al proveedor de IA configurado (usa la key real del .env)';

    public function handle(ProveedorIA $ia): int
    {
        $proveedor = (string) config('ia.proveedor');
        $modelo = config("ia.proveedores.{$proveedor}.modelo");
        $tieneKey = trim((string) config("ia.proveedores.{$proveedor}.api_key")) !== '';

        $this->line('Proveedor: ' . $proveedor);
        $this->line('Modelo:    ' . ($modelo ?: '(no configurado)'));
        // Solo si hay key o no; su valor nunca se imprime
        $this->line('API key:   ' . ($tieneKey ? 'configurada' : 'FALTA'));
        $this->newLine();

        $respuesta = $ia->responder([MensajeIA::usuario('Responde solo: hola')]);

        if ($respuesta->falla) {
            $this->error('La IA no respondió. Mensaje que vería el visitante:');
            $this->line($respuesta->texto);
            $this->newLine();
            $this->line('Motivo (diagnóstico): ' . $respuesta->motivo);

            return self::FAILURE;
        }

        $this->info('Respuesta:');
        $this->line($respuesta->texto);

        return self::SUCCESS;
    }
}
