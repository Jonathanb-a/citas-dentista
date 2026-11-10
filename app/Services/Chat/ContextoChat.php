<?php

namespace App\Services\Chat;

/**
 * Estado de UNA petición del chat, compartido entre el bucle y las herramientas. Se crea en
 * ChatService y se descarta al responder: nada de esto se guarda.
 */
class ContextoChat
{
    /** El visitante ya procesó una llamada válida a agendar_cita en esta petición */
    public bool $agendarProcesado = false;

    /** La cita quedó creada en esta petición */
    public bool $agendada = false;

    /** Token de la propuesta nueva que viaja de vuelta al navegador */
    public ?string $propuestaNueva = null;

    /** Texto que escribe el servidor (resumen o confirmación) y se devuelve sin otra llamada a la IA */
    public ?string $respuestaDirecta = null;

    /**
     * Botones {texto, mensaje} que el servidor agrega a la respuesta (ver OpcionesChat). Los fija la
     * última herramienta de consulta que se ejecutó; nunca vienen del modelo
     *
     * @var array<int, array{texto: string, mensaje: string}>
     */
    public array $opciones = [];

    /**
     * @param array|null $propuestaVigente datos de la propuesta que el visitante trae firmada de la
     *                                     petición anterior (ya verificada), o null
     */
    public function __construct(
        public readonly string $ip,
        public readonly string $mensaje,
        public readonly ?array $propuestaVigente = null,
    ) {}
}
