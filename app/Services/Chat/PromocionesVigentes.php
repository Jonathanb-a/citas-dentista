<?php

namespace App\Services\Chat;

use App\Models\Anuncio;

/**
 * Anuncios y promociones VIGENTES hoy (mismo filtro y orden que el carrusel de la landing, con el
 * "hoy" de la zona del consultorio) como bloque de solo lectura para las instrucciones. Máximo
 * `chat.instrucciones.max_anuncios`; de cada uno solo tipo, título, descripción corta y la fecha
 * hasta la que vale. Un texto que parece una orden para el modelo se omite completo y el siguiente
 * ocupa su lugar: el resultado es el mismo que si ese anuncio no existiera.
 */
class PromocionesVigentes
{
    public function __construct(private DatosParaPrompt $datos) {}

    /** Lista de líneas, o '' si no hay ninguno vigente */
    public function lineas(): string
    {
        $maximo = (int) config('chat.instrucciones.max_anuncios');
        $tituloMax = (int) config('chat.instrucciones.titulo_max');
        $descripcionMax = (int) config('chat.instrucciones.descripcion_max');

        try {
            // Un margen sobre el máximo, por si algunos se omiten
            $anuncios = Anuncio::vigentes()->ordenados()->limit(max(1, $maximo) * 4)->get();
        } catch (\Throwable $e) {
            return '';
        }

        $lineas = [];

        foreach ($anuncios as $anuncio) {
            if (count($lineas) >= $maximo) {
                break;
            }

            if ($this->datos->pareceInstruccion($anuncio->titulo) || $this->datos->pareceInstruccion($anuncio->descripcion)) {
                $this->datos->registrarOmitido('el anuncio', (int) $anuncio->id);

                continue;
            }

            $titulo = $this->datos->limpiar($anuncio->titulo, $tituloMax);

            if ($titulo === '') {
                continue;
            }

            $descripcion = $this->datos->limpiar($anuncio->descripcion, $descripcionMax);
            $tipo = Anuncio::TIPOS[$anuncio->tipo] ?? 'Aviso';

            $lineas[] = '- ' . $tipo . ': ' . $titulo
                . ($descripcion !== '' ? ' — ' . $descripcion : '')
                . ($anuncio->fecha_fin ? ' (vigente hasta el ' . $anuncio->fecha_fin->format('d/m/Y') . ')' : '');
        }

        return implode("\n", $lineas);
    }
}
