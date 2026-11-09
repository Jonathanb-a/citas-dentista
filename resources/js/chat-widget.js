// Widget de chat de la landing (Alpine.data). Habla con POST /chat (ver CLAUDE.md, "paso 4").
//
// Reglas:
// - La conversación vive SOLO en la memoria de la página: no se guarda nada en el navegador
//   (ni almacenamiento local o de sesión, ni bases de datos, ni cookies propias). Al recargar se
//   pierde, a propósito (puede traer datos personales)
// - Todo texto se pinta con x-text (texto, nunca HTML)
// - Al servidor solo viajan turnos completos con respuesta firmada: una pregunta que terminó
//   en error o en 429 no entra al historial
// - `pendiente` es la propuesta firmada de agendado que devolvió la última respuesta; se
//   manda tal cual en la petición siguiente

const TURNOS_MAXIMOS = 6; // 12 mensajes: el tope del servidor
const ESPERA_LENTA_MS = 15000; // pasado esto, "Sigo pensando…"
const TIEMPO_MAXIMO_MS = 35000;
const ESPERA_CORTA_MAXIMA = 120; // un Retry-After mayor es el límite diario: sin cuenta regresiva

export default function chatWidget() {
    return {
        abierto: false,
        esMovil: false,
        sinLeer: false,
        texto: '',
        mensajes: [], // lo que se ve: { id, rol: 'user' | 'assistant' | 'aviso', texto, estado?, ... }
        turnos: [], // lo que se manda: { usuario, asistente, firma } solo de preguntas contestadas
        pendiente: null,
        esperando: false,
        lento: false,
        bloqueoSegundos: 0,
        max: 500,
        url: '',
        urlToken: '',
        token: '',
        _id: 0,
        _generacion: 0, // sube al reiniciar: las respuestas de antes se ignoran
        _controlador: null,
        _temporizadores: [],
        _cuenta: null,

        init() {
            const datos = this.$el.dataset;
            this.url = datos.url;
            this.urlToken = datos.urlToken;
            this.token = datos.token;
            this.max = Number.parseInt(datos.max, 10) || 500;

            this.evitarElPie();

            // Móvil = pantalla completa y modal (el mismo corte que `sm` de Tailwind)
            const consulta = window.matchMedia('(max-width: 639px)');
            this.esMovil = consulta.matches;
            consulta.addEventListener('change', (e) => {
                this.esMovil = e.matches;
                this.bloquearScroll();
            });
        },

        // El botón nunca tapa el pie (ahí están los íconos de redes y WhatsApp): cuando el pie entra a la
        // pantalla, el botón y la ventana suben justo lo que mide la parte visible del pie. Solo mueve el
        // widget (variable CSS --chat-subir); el diseño de la landing no cambia
        evitarElPie() {
            const pie = document.querySelector('footer');
            if (!pie) return;

            let pendiente = false;
            let actual = 0;
            const calcular = () => {
                pendiente = false;
                const subir = Math.max(0, Math.round(window.innerHeight - pie.getBoundingClientRect().top));
                if (subir === actual) return;
                actual = subir;
                this.$el.style.setProperty('--chat-subir', subir + 'px');
            };
            const pedir = () => {
                if (pendiente) return;
                pendiente = true;
                requestAnimationFrame(calcular);
            };

            window.addEventListener('scroll', pedir, { passive: true });
            window.addEventListener('resize', pedir);
            pedir();
        },

        // ---------- Apertura ----------

        alternar() {
            this.abierto ? this.cerrar() : this.abrir();
        },

        abrir() {
            this.abierto = true;
            this.sinLeer = false;
            this.bloquearScroll();
            this.$nextTick(() => {
                // En pantallas táctiles el foco va a la ventana: enfocar el campo abriría el teclado de golpe
                (window.matchMedia('(pointer: coarse)').matches ? this.$refs.ventana : this.$refs.campo)?.focus();
                this.scrollAbajo();
            });
        },

        cerrar() {
            if (!this.abierto) return;
            this.abierto = false;
            this.bloquearScroll();
            this.$refs.boton?.focus();
        },

        // En pantalla completa el fondo no se desplaza (igual que el lightbox de la galería)
        bloquearScroll() {
            document.documentElement.style.overflow = this.abierto && this.esMovil ? 'hidden' : '';
        },

        // Tab no sale de la ventana cuando es modal (pantalla completa)
        atraparFoco(evento) {
            if (!this.esMovil) return;
            const enfocables = [...this.$refs.ventana.querySelectorAll('button, textarea, [href]')].filter((e) => !e.disabled && e.offsetParent !== null);
            if (enfocables.length === 0) return;
            const primero = enfocables[0];
            const ultimo = enfocables[enfocables.length - 1];
            if (evento.shiftKey && (document.activeElement === primero || document.activeElement === this.$refs.ventana)) {
                evento.preventDefault();
                ultimo.focus();
            } else if (!evento.shiftKey && document.activeElement === ultimo) {
                evento.preventDefault();
                primero.focus();
            }
        },

        // ---------- Estado derivado ----------

        // Caracteres (no unidades UTF-16), como los cuenta el servidor
        get contador() {
            return [...this.texto].length;
        },

        get hayMensajesDelVisitante() {
            return this.mensajes.some((m) => m.rol === 'user');
        },

        get puedeEnviar() {
            return !this.esperando && this.bloqueoSegundos === 0 && this.texto.trim() !== '' && this.contador <= this.max;
        },

        puedeReintentar(aviso) {
            return aviso.reintentar && !this.esperando && this.bloqueoSegundos === 0 && this.mensajes[this.mensajes.length - 1]?.id === aviso.id;
        },

        // ---------- Escribir ----------

        // Enter envía; Shift+Enter, salto de línea; Enter al elegir una palabra del teclado (IME) no cuenta
        alEnter(evento) {
            if (evento.shiftKey || evento.isComposing || evento.keyCode === 229) return;
            evento.preventDefault();
            this.enviar();
        },

        ajustarAlto() {
            const campo = this.$refs.campo;
            campo.style.height = 'auto';
            campo.style.height = Math.min(campo.scrollHeight, 112) + 'px';
        },

        // Historial para el servidor: solo turnos completos y firmados, los últimos 6
        historial() {
            return this.turnos.slice(-TURNOS_MAXIMOS).flatMap((t) => [
                { rol: 'user', texto: t.usuario },
                { rol: 'assistant', texto: t.asistente, firma: t.firma },
            ]);
        },

        enviar(forzado) {
            const texto = (typeof forzado === 'string' ? forzado : this.texto).trim();
            const valido = !this.esperando && this.bloqueoSegundos === 0 && texto !== '' && [...texto].length <= this.max;
            if (!valido) return;

            this.mensajes.push({ id: ++this._id, rol: 'user', texto, estado: 'enviando' });
            // Siempre se trabaja con el objeto que vigila Alpine (el de la lista), no con el original:
            // si no, cambiar su `estado` más tarde no actualizaría la pantalla
            const mensaje = this.mensajes[this.mensajes.length - 1];
            if (typeof forzado !== 'string') {
                this.texto = '';
                this.$nextTick(() => this.ajustarAlto());
            }
            this.esperando = true;
            this.scrollAbajo();
            this.pedir(mensaje);
        },

        reintentar(aviso) {
            if (!this.puedeReintentar(aviso)) return;
            const origen = this.mensajes.find((m) => m.id === aviso.de);
            this.mensajes = this.mensajes.filter((m) => m.id !== aviso.id);
            if (!origen) return;
            origen.estado = 'enviando';
            this.esperando = true;
            this.scrollAbajo();
            this.pedir(origen);
        },

        // ---------- Red ----------

        post(texto, senal) {
            return fetch(this.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ mensaje: texto, historial: this.historial(), pendiente: this.pendiente }),
                signal: senal,
            });
        },

        // Sesión caducada (419): se pide un token nuevo y se reintenta una sola vez, sin perder la conversación
        async renovarToken(senal) {
            const respuesta = await fetch(this.urlToken, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: senal });
            const datos = await respuesta.json();
            if (!respuesta.ok || typeof datos.token !== 'string' || datos.token === '') throw new Error('sin token');
            this.token = datos.token;
        },

        async pedir(mensaje) {
            const generacion = this._generacion;
            const controlador = new AbortController();
            this._controlador = controlador;
            const limite = setTimeout(() => controlador.abort(), TIEMPO_MAXIMO_MS);
            const lento = setTimeout(() => (this.lento = true), ESPERA_LENTA_MS);
            this._temporizadores.push(limite, lento);

            try {
                let respuesta = await this.post(mensaje.texto, controlador.signal);

                if (respuesta.status === 419) {
                    await this.renovarToken(controlador.signal);
                    respuesta = await this.post(mensaje.texto, controlador.signal);
                }

                if (generacion !== this._generacion) return;
                await this.atender(respuesta, mensaje);
            } catch (error) {
                if (generacion !== this._generacion) return;
                this.fallar(mensaje, 'No pude conectarme. Revisa tu conexión e intenta de nuevo.');
            } finally {
                clearTimeout(limite);
                clearTimeout(lento);
                if (generacion === this._generacion) {
                    this.esperando = false;
                    this.lento = false;
                    this.scrollAbajo();
                }
            }
        },

        async atender(respuesta, mensaje) {
            let datos = null;
            try {
                datos = await respuesta.json();
            } catch (e) {
                datos = null; // un 5xx de un proxy trae HTML, no JSON
            }

            const textoServidor = typeof datos?.respuesta === 'string' && datos.respuesta !== '' ? datos.respuesta : null;

            if (respuesta.status === 200 && datos?.ok === true && textoServidor && typeof datos.firma === 'string') {
                mensaje.estado = 'ok';
                this.mensajes.push({ id: ++this._id, rol: 'assistant', texto: textoServidor });
                this.turnos.push({ usuario: mensaje.texto, asistente: textoServidor, firma: datos.firma });
                // null borra la propuesta: solo sirve para el turno siguiente
                this.pendiente = typeof datos.pendiente === 'string' && datos.pendiente !== '' ? datos.pendiente : null;
                if (!this.abierto) this.sinLeer = true;
            } else if (respuesta.status === 200 && datos?.ok === false) {
                this.fallar(mensaje, textoServidor ?? 'Por ahora no puedo responderte. Intenta de nuevo en un momento.');
            } else if (respuesta.status === 422) {
                // Con historial, lo más probable es que la conversación ya no sea válida: se ofrece empezar de nuevo
                this.fallar(mensaje, textoServidor ?? 'No pude procesar tu mensaje.', { reintentar: false, reiniciar: this.turnos.length > 0 });
            } else if (respuesta.status === 429) {
                const espera = Number.parseInt(respuesta.headers.get('Retry-After'), 10);
                this.fallar(mensaje, textoServidor ?? 'Espera un momento y vuelve a intentarlo.');
                if (espera > 0 && espera <= ESPERA_CORTA_MAXIMA) this.iniciarCuenta(espera);
            } else if (respuesta.status === 419) {
                this.fallar(mensaje, 'Tu sesión caducó. Recarga la página para seguir chateando.', { reintentar: false, recargar: true });
            } else {
                this.fallar(mensaje, 'No pude conectarme. Revisa tu conexión e intenta de nuevo.');
            }
        },

        // El mensaje no se incluye en el historial: queda como "No se envió" con su aviso
        fallar(mensaje, texto, opciones = {}) {
            mensaje.estado = 'error';
            this.mensajes.push({
                id: ++this._id,
                rol: 'aviso',
                texto,
                de: mensaje.id,
                reintentar: opciones.reintentar ?? true,
                reiniciar: opciones.reiniciar ?? false,
                recargar: opciones.recargar ?? false,
            });
        },

        iniciarCuenta(segundos) {
            clearInterval(this._cuenta);
            this.bloqueoSegundos = segundos;
            this._cuenta = setInterval(() => {
                this.bloqueoSegundos = Math.max(0, this.bloqueoSegundos - 1);
                if (this.bloqueoSegundos === 0) clearInterval(this._cuenta);
            }, 1000);
        },

        // ---------- Reiniciar ----------

        reiniciar() {
            this._generacion++; // la respuesta que venga en camino se ignora
            this._controlador?.abort();
            this._temporizadores.forEach(clearTimeout);
            this._temporizadores = [];
            clearInterval(this._cuenta);
            this.mensajes = [];
            this.turnos = [];
            this.pendiente = null;
            this.texto = '';
            this.esperando = false;
            this.lento = false;
            this.bloqueoSegundos = 0;
            this.sinLeer = false;
            this.$nextTick(() => {
                this.ajustarAlto();
                this.$refs.lista.scrollTop = 0;
                this.$refs.campo.focus();
            });
        },

        recargar() {
            window.location.reload();
        },

        scrollAbajo() {
            this.$nextTick(() => {
                const lista = this.$refs.lista;
                if (!lista) return;
                const reducir = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                lista.scrollTo({ top: lista.scrollHeight, behavior: reducir ? 'auto' : 'smooth' });
            });
        },
    };
}
