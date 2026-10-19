// Carrusel de anuncios de la landing (Alpine.data). Las slides vienen de Blade;
// aquí solo se maneja cuál se ve: avance automático, pausa y deslizar con el dedo.
const SEGUNDOS_POR_SLIDE = 6;
const UMBRAL_DESLIZAR_PX = 50;

export default function carruselAnuncios(total) {
    return {
        actual: 0,
        total,
        pausado: false,
        temporizador: null,
        inicioX: null,

        init() {
            // Con una sola slide no hay nada que avanzar; con "reducir movimiento" tampoco sola
            const reducirMovimiento = window.matchMedia
                && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (this.total < 2 || reducirMovimiento) return;

            this.iniciarTemporizador();
        },

        destroy() {
            clearInterval(this.temporizador);
        },

        iniciarTemporizador() {
            clearInterval(this.temporizador);
            this.temporizador = setInterval(() => {
                if (!this.pausado && !document.hidden) this.siguiente(false);
            }, SEGUNDOS_POR_SLIDE * 1000);
        },

        // manual = true cuando lo pidió el visitante: el conteo vuelve a empezar
        siguiente(manual = true) {
            this.ir((this.actual + 1) % this.total, manual);
        },

        anterior() {
            this.ir((this.actual - 1 + this.total) % this.total, true);
        },

        ir(indice, manual = true) {
            this.actual = indice;
            if (manual && this.temporizador) this.iniciarTemporizador();
        },

        empezarToque(evento) {
            this.inicioX = evento.changedTouches[0].clientX;
            this.pausado = true;
        },

        terminarToque(evento) {
            if (this.inicioX !== null) {
                const distancia = evento.changedTouches[0].clientX - this.inicioX;

                if (Math.abs(distancia) > UMBRAL_DESLIZAR_PX) {
                    distancia < 0 ? this.siguiente() : this.anterior();
                }
            }

            this.inicioX = null;
            this.pausado = false;
        },
    };
}
