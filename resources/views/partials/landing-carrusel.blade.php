{{-- NOVEDADES Y PROMOCIONES (carrusel de anuncios). La landing solo la incluye si hay anuncios vigentes --}}
@php
    $total = $anuncios->count();
    $varias = $total > 1;
    $foco = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#a13153]';
    $control = 'flex h-11 w-11 items-center justify-center rounded-full border border-[#fbd3df] bg-white text-gray-700 hover:border-[#c03a63] hover:text-[#c03a63] transition-[border-color,color] duration-300 ' . $foco;
@endphp
<section id="novedades" data-carrusel class="w-full bg-[#FFF6F6] py-16 md:py-24 px-6 md:px-[108px] scroll-mt-[82px] md:scroll-mt-[100px]"
         aria-roledescription="carrusel" aria-label="Novedades y promociones" style="font-family: 'Montserrat', sans-serif;">

    {{-- Titulo --}}
    <h2 class="font-bold text-gray-800 text-3xl md:text-5xl text-center mb-12">
        NOVEDADES Y PROMOCIONES
    </h2>

    <div class="max-w-[1100px] mx-auto"
         @if($varias)
         x-data="carruselAnuncios({{ $total }})"
         @keydown.arrow-left.prevent="anterior()" @keydown.arrow-right.prevent="siguiente()"
         @endif>

        {{-- Slides: todas apiladas en la misma celda para que la altura no cambie al pasar --}}
        <div class="grid touch-pan-y"
             @if($varias)
             :aria-live="detenido || !automatico ? 'polite' : 'off'"
             @mouseenter="pausado = true" @mouseleave="pausado = false"
             @focusin="pausado = true" @focusout="pausado = false"
             @touchstart.passive="empezarToque($event)" @touchend="terminarToque($event)"
             @endif>

            @foreach($anuncios as $i => $anuncio)
                @php
                    $paleta = $anuncio->paleta();
                    $imagen = $anuncio->imagenUrl();
                @endphp
                <article class="col-start-1 row-start-1 rounded-3xl border {{ $paleta['borde'] }} bg-white shadow-sm overflow-hidden grid {{ $imagen ? 'md:grid-cols-2' : '' }} transition-opacity duration-500"
                         @if($varias)
                         @if($i > 0) style="opacity: 0; pointer-events: none;" @endif
                         :style="{ opacity: actual === {{ $i }} ? 1 : 0, pointerEvents: actual === {{ $i }} ? 'auto' : 'none', zIndex: actual === {{ $i }} ? 1 : 0 }"
                         :aria-hidden="actual !== {{ $i }}"
                         :inert="actual !== {{ $i }}"
                         role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} de {{ $total }}"
                         @endif>

                    @if($imagen)
                        <img src="{{ $imagen }}" alt="{{ $anuncio->titulo }}"
                             @if($i > 0) loading="lazy" @endif decoding="async"
                             width="800" height="600" class="w-full h-60 md:h-full md:min-h-[360px] object-cover bg-[#fde7ed]">
                    @endif

                    <div class="flex flex-col gap-4 p-8 md:p-12 justify-center {{ $imagen ? 'items-start text-left' : 'items-center text-center' }}">

                        {{-- Etiqueta del tipo --}}
                        <span class="text-xs font-bold tracking-widest px-3 py-1.5 rounded-full {{ $paleta['franja'] }}">
                            {{ mb_strtoupper(\App\Models\Anuncio::TIPOS[$anuncio->tipo] ?? $anuncio->tipo) }}
                        </span>

                        @if($anuncio->emoji && !$imagen)
                            <div class="w-20 h-20 {{ $paleta['circulo'] }} rounded-full flex items-center justify-center">
                                <span class="text-4xl leading-none">{{ $anuncio->emoji }}</span>
                            </div>
                        @endif

                        <h3 class="font-extrabold text-gray-800 text-2xl md:text-4xl leading-tight tracking-tight">
                            @if($anuncio->emoji && $imagen)<span>{{ $anuncio->emoji }}</span> @endif{{ $anuncio->titulo }}
                        </h3>

                        @if($anuncio->descripcion)
                            <p class="text-gray-600 text-sm md:text-base leading-relaxed">{{ $anuncio->descripcion }}</p>
                        @endif

                        @if($anuncio->fecha_fin)
                            <p class="font-semibold text-gray-600 text-xs">Válido hasta el {{ $anuncio->fecha_fin->format('d/m/Y') }}</p>
                        @endif

                        @if($anuncio->muestraBoton())
                            <a href="{{ $anuncio->enlaceBoton() }}"
                               @if($anuncio->enlaceEsExterno()) target="_blank" rel="noopener noreferrer" @endif
                               class="inline-flex min-h-11 items-center {{ $paleta['boton'] }} text-white text-sm font-semibold px-8 py-3 rounded-2xl transition-[background-color] duration-300 {{ $foco }}">
                                {{ $anuncio->textoBoton() }}
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Controles (solo con más de una slide). Las flechas del teclado también pasan de slide --}}
        @if($varias)
            <div class="flex flex-wrap items-center justify-center gap-2 mt-8">

                {{-- Anterior --}}
                <button type="button" @click="anterior()" aria-label="Anterior" class="{{ $control }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                {{-- Puntos: el área táctil es de 44 px aunque el punto se vea chico --}}
                @for($i = 0; $i < $total; $i++)
                    <button type="button" @click="ir({{ $i }})" aria-label="Ir a la slide {{ $i + 1 }}"
                            :aria-current="actual === {{ $i }} ? 'true' : 'false'"
                            class="flex h-11 min-w-11 items-center justify-center rounded-full {{ $foco }}">
                        <span class="block h-2.5 rounded-full transition-[width,background-color] duration-300"
                              :class="actual === {{ $i }} ? 'bg-[#c03a63] w-6' : 'bg-gray-300 w-2.5'"></span>
                    </button>
                @endfor

                {{-- Siguiente --}}
                <button type="button" @click="siguiente()" aria-label="Siguiente" class="{{ $control }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                {{-- Pausar / reanudar el avance automático (no aparece si no hay avance: "reducir movimiento") --}}
                <button type="button" x-show="automatico" style="display: none" @click="alternarPausa()"
                        :aria-label="detenido ? 'Reanudar el avance automático' : 'Pausar el avance automático'"
                        class="{{ $control }}">
                    <svg x-show="!detenido" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5h3v14H7zM14 5h3v14h-3z"/></svg>
                    <svg x-show="detenido" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                </button>
            </div>
        @endif
    </div>

</section>
