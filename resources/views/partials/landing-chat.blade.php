{{--
    Widget de chat de la landing (resources/js/chat-widget.js). La landing solo lo incluye si el chat
    está activo y el proveedor de IA tiene key. Contrato con POST /chat: ver CLAUDE.md.

    - La conversación vive solo en la memoria de la página (no se guarda nada en el navegador)
    - Todo lo que escribe el visitante o responde la IA se pinta con x-text (texto, nunca HTML)
    - Los datos del servidor viajan en atributos data-* escapados por Blade
--}}
@php
    $consultorio = config('consultorio.nombre');
    $foco = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#a13153]';
    $botonCabecera = 'flex h-11 w-11 items-center justify-center rounded-full text-gray-700 hover:bg-white/80 transition-[background-color] duration-300 ' . $foco;
@endphp
<!-- chat-widget:inicio -->
<div data-chat-widget x-data="chatWidget" style="display: none; --chat-subir: 0px; font-family: 'Montserrat', sans-serif;" x-show="true"
     data-url="{{ route('chat.enviar') }}" data-url-token="{{ route('chat.token') }}" data-token="{{ csrf_token() }}"
     data-max="{{ config('chat.max_caracteres') }}"
     @keydown.escape.window="if (abierto) cerrar()" class="print:hidden">

    {{-- Botón flotante --}}
    <button type="button" x-ref="boton" @click="alternar()"
            :aria-expanded="abierto ? 'true' : 'false'" aria-controls="chat-ventana"
            aria-label="Abrir el chat para agendar tu cita"
            class="fixed bottom-[calc(1rem+var(--chat-subir))] right-4 z-40 inline-flex h-14 items-center justify-center gap-2 rounded-full bg-[#c03a63] px-4 text-white shadow-lg hover:bg-[#a13153] transition-[background-color] duration-300 sm:bottom-[calc(1.5rem+var(--chat-subir))] sm:right-6 sm:px-6 {{ $foco }}">
        <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.6 8.6 0 0 1-3.6-.8L3 21l1.9-5.1A8.4 8.4 0 0 1 3 11.5 8.5 8.5 0 0 1 12 3a8.5 8.5 0 0 1 9 8.5z"/></svg>
        <span class="hidden text-sm font-semibold sm:inline">Agenda tu cita</span>
        <span x-show="sinLeer" style="display: none" class="absolute -right-0.5 -top-0.5 h-4 w-4 rounded-full border-2 border-white bg-[#15626C]">
            <span class="sr-only">Hay una respuesta nueva</span>
        </span>
    </button>

    {{-- Ventana: casi toda la pantalla en celular; desde sm, 380 px abajo a la derecha --}}
    <div id="chat-ventana" x-ref="ventana" x-show="abierto" style="display: none" role="dialog"
         aria-labelledby="chat-titulo" :aria-modal="esMovil ? 'true' : 'false'" tabindex="-1"
         @keydown.tab="atraparFoco($event)"
         x-transition:enter="motion-safe:transition motion-safe:duration-200" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="motion-safe:transition motion-safe:duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-3"
         class="fixed inset-2 z-[55] flex flex-col overflow-hidden rounded-3xl border border-[#fbd3df] bg-[#FFF6F6] shadow-2xl focus:outline-none sm:inset-auto sm:bottom-[calc(6rem+var(--chat-subir))] sm:right-6 sm:h-[min(640px,calc(100dvh-8rem-var(--chat-subir)))] sm:w-[380px]">

        {{-- Encabezado --}}
        <div class="flex items-center gap-3 bg-linear-to-br from-[#FFF6F6] to-[#fde7ed] px-4 py-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-white shadow-sm" aria-hidden="true">
                <svg class="h-6 w-6" viewBox="40 40 160 160"><path d="M120 62c-14-12-38-18-56-4-20 15-18 44-8 66 7 15 9 29 11 45 2 16 6 34 18 34 11 0 13-18 16-34 2-10 6-18 19-18s17 8 19 18c3 16 5 34 16 34 12 0 16-18 18-34 2-16 4-30 11-45 10-22 12-51-8-66-18-14-42-8-56 4z" fill="#ffffff" stroke="#d75078" stroke-width="8" stroke-linejoin="round"/></svg>
            </span>
            <div class="min-w-0 flex-1">
                <h2 id="chat-titulo" class="truncate text-base font-extrabold text-gray-800">Asistente virtual</h2>
                <p class="truncate text-xs text-gray-600">Dra. {{ $consultorio }}</p>
            </div>
            <button type="button" @click="reiniciar()" aria-label="Reiniciar la conversación" title="Reiniciar la conversación" class="{{ $botonCabecera }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
            </button>
            <button type="button" x-ref="cerrar" @click="cerrar()" aria-label="Cerrar el chat" class="{{ $botonCabecera }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        {{-- Mensajes --}}
        <div x-ref="lista" role="log" aria-live="polite" aria-relevant="additions" aria-label="Conversación"
             class="flex-1 space-y-3 overflow-y-auto overscroll-contain px-4 py-4">

            {{-- Bienvenida: la escribe Blade; no entra al historial que se manda al servidor --}}
            <div class="flex justify-start">
                <div class="max-w-[85%] whitespace-pre-wrap break-words rounded-2xl rounded-bl-md border border-[#fbd3df] bg-white px-4 py-2.5 text-sm leading-relaxed text-gray-800">¡Hola! Soy la asistente virtual del consultorio de la Dra. {{ rtrim($consultorio, '.') }}. Te ayudo a agendar tu cita. Por favor no compartas información médica ni síntomas por aquí; para eso contacta al consultorio.</div>
            </div>

            <template x-for="m in mensajes" :key="m.id">
                <div class="flex flex-col" :class="m.rol === 'user' ? 'items-end' : 'items-start'">
                    <div class="max-w-[85%] whitespace-pre-wrap break-words rounded-2xl px-4 py-2.5 text-sm leading-relaxed"
                         :role="m.rol === 'aviso' ? 'alert' : null"
                         :class="{
                             'rounded-br-md bg-[#c03a63] text-white': m.rol === 'user',
                             'rounded-bl-md border border-[#fbd3df] bg-white text-gray-800': m.rol === 'assistant',
                             'rounded-bl-md border border-[#fbd3df] bg-[#fde7ed] text-gray-800': m.rol === 'aviso'
                         }" x-text="m.texto"></div>

                    {{-- Botones que agrega el servidor: tocar uno envía su mensaje como si el visitante lo escribiera --}}
                    <div x-show="m.opciones && m.opciones.length > 0" style="display: none" role="group" aria-label="Opciones para continuar" class="mt-2 flex max-w-full flex-wrap gap-2">
                        <template x-for="(o, i) in (m.opciones ?? [])" :key="i">
                            <button type="button" :disabled="!opcionesActivas(m)" @click="elegir(m, o)"
                                    class="min-h-11 max-w-full rounded-full border border-[#c03a63] bg-white px-4 py-2 text-left text-sm font-semibold text-[#a13153] hover:bg-[#fde7ed] disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-white transition-[background-color] duration-300 {{ $foco }}"
                                    x-text="o.texto"></button>
                        </template>
                    </div>

                    <p x-show="m.rol === 'user' && m.estado === 'error'" style="display: none" class="mt-1 text-xs font-semibold text-[#a13153]">No se envió</p>

                    <div x-show="m.rol === 'aviso' && (m.reiniciar || m.recargar || puedeReintentar(m))" style="display: none" class="mt-2 flex flex-wrap gap-2">
                        <button type="button" x-show="puedeReintentar(m)" style="display: none" @click="reintentar(m)"
                                class="min-h-11 rounded-full border border-[#c03a63] bg-white px-4 text-sm font-semibold text-[#a13153] hover:bg-[#fde7ed] transition-[background-color] duration-300 {{ $foco }}">Reintentar</button>
                        <button type="button" x-show="m.reiniciar" style="display: none" @click="reiniciar()"
                                class="min-h-11 rounded-full border border-[#c03a63] bg-white px-4 text-sm font-semibold text-[#a13153] hover:bg-[#fde7ed] transition-[background-color] duration-300 {{ $foco }}">Empezar de nuevo</button>
                        <button type="button" x-show="m.recargar" style="display: none" @click="recargar()"
                                class="min-h-11 rounded-full bg-[#c03a63] px-4 text-sm font-semibold text-white hover:bg-[#a13153] transition-[background-color] duration-300 {{ $foco }}">Recargar la página</button>
                    </div>
                </div>
            </template>

            {{-- Escribiendo --}}
            <div x-show="esperando" style="display: none" role="status" class="flex justify-start">
                <div class="flex items-center gap-2 rounded-2xl rounded-bl-md border border-[#fbd3df] bg-white px-4 py-3 text-sm text-gray-600">
                    <span class="flex items-center gap-1" aria-hidden="true">
                        <span class="chat-punto h-2 w-2 rounded-full bg-[#c03a63]"></span>
                        <span class="chat-punto h-2 w-2 rounded-full bg-[#c03a63]"></span>
                        <span class="chat-punto h-2 w-2 rounded-full bg-[#c03a63]"></span>
                    </span>
                    <span x-text="lento ? 'Sigo pensando…' : 'Escribiendo…'"></span>
                </div>
            </div>

            {{-- Sugerencias: hasta que el visitante escribe algo --}}
            <div x-show="!hayMensajesDelVisitante && !esperando" style="display: none" class="flex flex-wrap gap-2 pt-1">
                <button type="button" @click="enviar('Quiero agendar una cita')"
                        class="min-h-11 rounded-full border border-[#c03a63] bg-white px-4 text-sm font-semibold text-[#a13153] hover:bg-[#fde7ed] transition-[background-color] duration-300 {{ $foco }}">Agendar una cita</button>
                <button type="button" @click="enviar('¿Qué servicios tienen?')"
                        class="min-h-11 rounded-full border border-[#c03a63] bg-white px-4 text-sm font-semibold text-[#a13153] hover:bg-[#fde7ed] transition-[background-color] duration-300 {{ $foco }}">Ver servicios</button>
                <button type="button" @click="enviar('¿Qué horarios tienen disponibles?')"
                        class="min-h-11 rounded-full border border-[#c03a63] bg-white px-4 text-sm font-semibold text-[#a13153] hover:bg-[#fde7ed] transition-[background-color] duration-300 {{ $foco }}">Ver horarios</button>
            </div>
        </div>

        {{-- Escribir --}}
        <form @submit.prevent="enviar()" class="border-t border-[#fbd3df] bg-white px-3 py-3">
            <p x-show="bloqueoSegundos > 0" style="display: none" role="status" class="mb-2 text-xs font-semibold text-[#a13153]"
               x-text="'Espera un momento: puedes escribir de nuevo en ' + bloqueoSegundos + ' s'"></p>
            <div class="flex items-end gap-2">
                <label for="chat-mensaje" class="sr-only">Escribe tu mensaje</label>
                <textarea id="chat-mensaje" x-ref="campo" x-model="texto" rows="1" :maxlength="max"
                          @keydown.enter="alEnter($event)" @input="ajustarAlto()"
                          placeholder="Escribe tu mensaje…" aria-describedby="chat-contador" enterkeyhint="send" autocomplete="off"
                          class="max-h-28 min-h-11 flex-1 resize-none rounded-2xl border border-[#fbd3df] bg-[#FFF6F6] px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-500 {{ $foco }}"></textarea>
                <button type="submit" :disabled="!puedeEnviar" aria-label="Enviar mensaje"
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#c03a63] text-white hover:bg-[#a13153] disabled:cursor-not-allowed disabled:opacity-50 transition-[background-color] duration-300 {{ $foco }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7z"/></svg>
                </button>
            </div>
            <div class="mt-1.5 flex items-center justify-between gap-2 text-xs text-gray-600">
                <span>Enter envía · Shift+Enter, salto de línea</span>
                <span id="chat-contador" :class="contador >= max - 50 ? 'font-bold text-[#a13153]' : ''" x-text="contador + '/' + max"></span>
            </div>
        </form>
    </div>
</div>
<!-- chat-widget:fin -->
