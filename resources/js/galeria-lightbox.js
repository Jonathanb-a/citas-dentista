// Lightbox de la galería de la landing (Alpine.data). Las fotos ya están en el HTML
// (Blade las escribe en cada [data-galeria-foto], escapadas); aquí solo se leen sus
// datos y se maneja cuál se ve ampliada: teclado, foco y cerrar.
export default function galeriaLightbox() {
    return {
        fotos: [],
        actual: 0,
        abierta: false,
        origen: null, // botón que abrió el lightbox: al cerrar, el foco vuelve ahí

        init() {
            this.fotos = [...this.$el.querySelectorAll('[data-galeria-foto]')].map((foto) => ({
                src: foto.dataset.src,
                titulo: foto.dataset.titulo,
                subtitulo: foto.dataset.subtitulo,
            }));
        },

        get foto() {
            return this.fotos[this.actual] ?? { src: '', titulo: '', subtitulo: '' };
        },

        abrir(indice, evento) {
            this.actual = indice;
            this.origen = evento?.currentTarget ?? null;
            this.abierta = true;
            document.documentElement.style.overflow = 'hidden';
            this.$nextTick(() => this.$refs.cerrar?.focus());
        },

        cerrar() {
            if (!this.abierta) return;
            this.abierta = false;
            document.documentElement.style.overflow = '';
            this.origen?.focus();
        },

        siguiente() {
            this.actual = (this.actual + 1) % this.fotos.length;
        },

        anterior() {
            this.actual = (this.actual - 1 + this.fotos.length) % this.fotos.length;
        },

        // Tab no sale del lightbox mientras está abierto
        atraparFoco(evento) {
            const enfocables = [...this.$refs.dialogo.querySelectorAll('button')].filter((b) => b.offsetParent !== null);
            if (enfocables.length === 0) return;

            const primero = enfocables[0];
            const ultimo = enfocables[enfocables.length - 1];

            if (evento.shiftKey && document.activeElement === primero) {
                evento.preventDefault();
                ultimo.focus();
            } else if (!evento.shiftKey && document.activeElement === ultimo) {
                evento.preventDefault();
                primero.focus();
            }
        },
    };
}
