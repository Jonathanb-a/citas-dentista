{{-- NOVEDADES Y PROMOCIONES (carrusel de anuncios) --}}
@php
    $total = $anuncios->count();
    $varias = $total > 1;
@endphp
<section id="novedades" data-carrusel class="w-full bg-white py-16 md:py-24 px-6 md:px-[108px]"
         aria-roledescription="carrusel" aria-label="Novedades y promociones">

    {{-- Titulo --}}
    <h2 class="font-bold text-gray-800 text-3xl md:text-5xl text-center mb-12"
        style="font-family: 'Montserrat', sans-serif;">
        NOVEDADES Y PROMOCIONES
    </h2>

    <div class="max-w-[1100px] mx-auto"
         @if($varias) x-data="carruselAnuncios({{ $total }})" @endif>

        {{-- Slides: todas apiladas en la misma celda para que la altura no cambie al pasar --}}
        <div class="grid touch-pan-y"
             @if($varias)
             @mouseenter="pausado = true" @mouseleave="pausado = false"
             @focusin="pausado = true" @focusout="pausado = false"
             @touchstart.passive="empezarToque($event)" @touchend="terminarToque($event)"
             @endif>

            @foreach($anuncios as $i => $anuncio)
                @php
                    $paleta = $anuncio->paleta();
                    $imagen = $anuncio->imagenUrl();
                @endphp
                <article class="col-start-1 row-start-1 rounded-2xl border {{ $paleta['borde'] }} shadow-sm overflow-hidden grid {{ $imagen ? 'md:grid-cols-2' : '' }} transition-opacity duration-500"
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
                             class="w-full h-56 md:h-full md:min-h-[320px] object-cover">
                    @endif

                    <div class="flex flex-col gap-4 p-8 md:p-12 justify-center {{ $imagen ? 'items-start text-left' : 'items-center text-center' }}">

                        {{-- Etiqueta del tipo --}}
                        <span class="text-xs font-bold tracking-wide px-3 py-1 rounded-full {{ $paleta['franja'] }}"
                              style="font-family: 'Montserrat', sans-serif;">
                            {{ mb_strtoupper(\App\Models\Anuncio::TIPOS[$anuncio->tipo] ?? $anuncio->tipo) }}
                        </span>

                        @if($anuncio->emoji && !$imagen)
                            <div class="w-16 h-16 {{ $paleta['circulo'] }} rounded-full flex items-center justify-center">
                                <span class="text-3xl leading-none">{{ $anuncio->emoji }}</span>
                            </div>
                        @endif

                        <h3 class="font-bold text-gray-800 text-2xl md:text-3xl leading-tight"
                            style="font-family: 'Montserrat', sans-serif;">
                            @if($anuncio->emoji && $imagen)<span>{{ $anuncio->emoji }}</span> @endif{{ $anuncio->titulo }}
                        </h3>

                        @if($anuncio->descripcion)
                            <p class="font-light text-gray-500 text-sm md:text-base leading-relaxed"
                               style="font-family: 'Montserrat', sans-serif;">{{ $anuncio->descripcion }}</p>
                        @endif

                        @if($anuncio->fecha_fin)
                            <p class="font-semibold text-gray-500 text-xs"
                               style="font-family: 'Montserrat', sans-serif;">Válido hasta el {{ $anuncio->fecha_fin->format('d/m/Y') }}</p>
                        @endif

                        @if($anuncio->muestraBoton())
                            <a href="{{ $anuncio->enlaceBoton() }}"
                               @if($anuncio->enlaceEsExterno()) target="_blank" rel="noopener noreferrer" @endif
                               class="inline-block {{ $paleta['boton'] }} text-white text-sm font-medium px-8 py-3 rounded-full transition-all duration-300"
                               style="font-family: 'Montserrat', sans-serif;">
                                {{ $anuncio->textoBoton() }}
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Controles (solo con más de una slide) --}}
        @if($varias)
            <div class="flex items-center justify-center gap-4 mt-10">

                {{-- Anterior --}}
                <button type="button" @click="anterior()" aria-label="Anterior"
                        class="w-10 h-10 rounded-full border border-gray-300 flex items-center justify-center hover:bg-[#f285a2] hover:border-[#f285a2] hover:text-white transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                {{-- Puntos --}}
                @for($i = 0; $i < $total; $i++)
                    <button type="button" @click="ir({{ $i }})" aria-label="Ir a la slide {{ $i + 1 }}"
                            class="w-2.5 h-2.5 rounded-full transition-all duration-300"
                            :class="actual === {{ $i }} ? 'bg-[#d75078] w-6' : 'bg-gray-300'">
                    </button>
                @endfor

                {{-- Siguiente --}}
                <button type="button" @click="siguiente()" aria-label="Siguiente"
                        class="w-10 h-10 rounded-full border border-gray-300 flex items-center justify-center hover:bg-[#f285a2] hover:border-[#f285a2] hover:text-white transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        @endif
    </div>

</section>
