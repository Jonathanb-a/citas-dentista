<x-layout>

    {{-- HERO --}}
    @php
        $focoHero = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#a13153]';
        $botonHero = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl bg-[#c03a63] px-7 py-3.5 text-sm md:text-base font-semibold text-white hover:bg-[#a13153] transition-[background-color] duration-300 ' . $focoHero;
    @endphp
    <section id="inicio" class="w-full min-h-[600px] md:min-h-[719px] bg-linear-to-br from-[#FFF6F6] to-[#fde7ed] flex items-center px-6 md:px-[108px] py-16">
        <div class="w-full flex flex-col md:flex-row items-center gap-12 md:gap-16">

            {{-- Contenido izquierda --}}
            <div class="w-full md:w-1/2 flex flex-col gap-6" style="font-family: 'Montserrat', sans-serif;">

                {{-- Frase superior --}}
                <span class="registro-entrada self-start rounded-full bg-white/80 px-4 py-1.5 text-xs md:text-sm font-bold tracking-widest text-[#c03a63] shadow-sm" style="--retraso: 0ms">
                    {{ $landing->texto('hero.etiqueta') }}
                </span>

                {{-- Titulo --}}
                <h1 class="registro-entrada font-extrabold text-gray-800 text-4xl sm:text-5xl lg:text-7xl leading-[1.05] tracking-tight" style="--retraso: 100ms">
                    {{ $landing->titulo('hero.titulo') }}
                </h1>

                {{-- Descripcion --}}
                <p class="registro-entrada text-gray-600 text-base md:text-xl leading-relaxed max-w-[540px]" style="--retraso: 200ms">
                    {{ $landing->texto('hero.descripcion') }}
                </p>

                {{-- Botones --}}
                <div class="registro-entrada flex flex-col sm:flex-row gap-3 mt-2" style="--retraso: 300ms">
                    <a href="{{ route('paciente.citas.create') }}" class="{{ $botonHero }}">
                        {{ $landing->texto('hero.boton') }}
                    </a>
                    <a href="#tratamientos" class="{{ $botonHero }}">
                        Ver tratamientos
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
                    </a>
                </div>

            </div>

            {{-- Imagen derecha: la de hero.imagen; si no carga, la ilustración del diente --}}
            <div class="registro-entrada-lateral w-full md:w-1/2 flex justify-center items-center" style="--retraso: 300ms"
                 x-data="{ rota: false }"
                 x-init="$nextTick(() => { rota = $refs.imagen.complete && $refs.imagen.naturalWidth === 0 })">
                <img src="{{ $landing->imagen('hero.imagen') }}"
                     alt="Dentista"
                     x-ref="imagen"
                     x-show="!rota"
                     x-on:error="rota = true"
                     class="w-full max-w-[579px] aspect-[579/423] object-cover rounded-3xl shadow-lg">
                <div x-show="rota" style="display: none" class="w-full max-w-[420px]">
                    <svg class="hero-flotar w-full h-auto" viewBox="0 0 240 240" aria-hidden="true">
                        <defs>
                            <linearGradient id="hero-diente" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#ffffff"/>
                                <stop offset="1" stop-color="#fde7ed"/>
                            </linearGradient>
                        </defs>
                        <circle cx="120" cy="120" r="104" fill="#fbd3df" opacity="0.6"/>
                        <path d="M120 62c-14-12-38-18-56-4-20 15-18 44-8 66 7 15 9 29 11 45 2 16 6 34 18 34 11 0 13-18 16-34 2-10 6-18 19-18s17 8 19 18c3 16 5 34 16 34 12 0 16-18 18-34 2-16 4-30 11-45 10-22 12-51-8-66-18-14-42-8-56 4z"
                              fill="url(#hero-diente)" stroke="#d75078" stroke-width="4" stroke-linejoin="round"/>
                        <path d="M84 82c6-6 16-8 24-4" fill="none" stroke="#f285a2" stroke-width="4" stroke-linecap="round"/>
                        <path class="hero-destello" d="M182 58l3 9 9 3-9 3-3 9-3-9-9-3 9-3z" fill="#d75078" style="--retraso: 0ms"/>
                        <path class="hero-destello" d="M54 166l2 6 6 2-6 2-2 6-2-6-6-2 6-2z" fill="#f285a2" style="--retraso: 1000ms"/>
                        <path class="hero-destello" d="M192 150l2 5 5 2-5 2-2 5-2-5-5-2 5-2z" fill="#f285a2" style="--retraso: 2000ms"/>
                    </svg>
                </div>
            </div>

        </div>
    </section>

    {{-- TRATAMIENTOS (sustituye a la cinta rosa fija): se oculta si no hay servicios --}}
@if(!empty($tratamientos) && $tratamientos->isNotEmpty())
    @include('partials.landing-tratamientos')
@endif

@if(!empty($anuncios) && $anuncios->isNotEmpty())
    @include('partials.landing-carrusel')
@endif

{{-- NOSOTROS: misión, visión y certificados en bloques compactos --}}
@include('partials.landing-nosotros')

{{-- GALERÍA: cuadrícula con lightbox (resources/js/galeria-lightbox.js). Se oculta si no hay fotos --}}
@if(count($galeria) > 0)
@php
    $focoGaleria = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#a13153]';
    $controlGaleria = 'flex h-11 w-11 items-center justify-center rounded-full bg-white/90 text-gray-800 hover:bg-white transition-[background-color] duration-300 ' . $focoGaleria;
@endphp
<section id="galeria" data-aparecer class="w-full bg-white py-16 md:py-24 px-6 md:px-[108px] scroll-mt-[82px] md:scroll-mt-[100px]" style="font-family: 'Montserrat', sans-serif;">

    {{-- Titulo --}}
    <h2 class="font-bold text-gray-800 text-3xl md:text-5xl text-center mb-12">
        {{ $landing->texto('galeria.titulo') }}
    </h2>

    <div x-data="galeriaLightbox()" @keydown.escape.window="cerrar()">

        {{-- Fotos: Blade las escribe (escapadas) y el lightbox lee sus datos. Con 1 o 2 fotos quedan centradas --}}
        <ul class="flex flex-wrap justify-center gap-6">
            @foreach($galeria as $i => $foto)
                <li data-galeria-foto data-src="{{ $foto['src'] }}" data-titulo="{{ $foto['titulo'] }}" data-subtitulo="{{ $foto['subtitulo'] }}"
                    class="w-full sm:w-[calc(50%-12px)] lg:w-[calc(33.333%-16px)]">
                    <button type="button" @click="abrir({{ $i }}, $event)"
                            aria-label="Ampliar foto: {{ $foto['titulo'] }}"
                            class="group block w-full text-left rounded-3xl {{ $focoGaleria }}">
                        <span class="block overflow-hidden rounded-3xl shadow-sm">
                            <img src="{{ $foto['src'] }}" alt="{{ $foto['titulo'] }}" loading="lazy" decoding="async"
                                 class="w-full h-[300px] md:h-[340px] object-cover motion-safe:transition-transform motion-safe:duration-500 motion-safe:group-hover:scale-105">
                        </span>
                        <span class="block px-2 pt-3">
                            <span class="block font-bold text-gray-800 text-base">{{ $foto['titulo'] }}</span>
                            <span class="block text-gray-600 text-sm">{{ $foto['subtitulo'] }}</span>
                        </span>
                    </button>
                </li>
            @endforeach
        </ul>

        {{-- Lightbox --}}
        <div x-show="abierta" style="display: none"
             x-transition:enter="motion-safe:transition-opacity motion-safe:duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="motion-safe:transition-opacity motion-safe:duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/90 p-4 md:p-10"
             @click.self="cerrar()">
            <div x-ref="dialogo" role="dialog" aria-modal="true" :aria-label="'Foto ampliada: ' + foto.titulo"
                 @keydown.tab="atraparFoco($event)"
                 @keydown.arrow-right.prevent="siguiente()" @keydown.arrow-left.prevent="anterior()"
                 class="relative flex w-full max-w-5xl flex-col items-center gap-4">
                <img :src="foto.src" :alt="foto.titulo" loading="lazy" class="max-h-[75vh] w-auto max-w-full rounded-2xl object-contain">
                <div class="text-center text-white">
                    <p class="font-bold text-lg" x-text="foto.titulo"></p>
                    <p class="text-sm text-white/80" x-text="foto.subtitulo"></p>
                    <p class="mt-1 text-xs text-white/80" x-show="fotos.length > 1" x-text="(actual + 1) + ' de ' + fotos.length"></p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" x-show="fotos.length > 1" @click="anterior()" aria-label="Foto anterior" class="{{ $controlGaleria }}">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" x-ref="cerrar" @click="cerrar()" aria-label="Cerrar" class="{{ $controlGaleria }}">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
                    </button>
                    <button type="button" x-show="fotos.length > 1" @click="siguiente()" aria-label="Foto siguiente" class="{{ $controlGaleria }}">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

</section>
@endif

{{-- OPINIONES DE PACIENTES: solo las autorizadas (pestaña "Opiniones" del panel). Sin ninguna, no se muestra --}}
@if(!empty($testimonios))
    @include('partials.landing-testimonios')
@endif

{{-- FRANJA PARA AGENDAR (título y texto editables: agendar.*) --}}
@include('partials.landing-agendar')

{{-- CONTACTO --}}
@include('partials.landing-contacto')

{{-- PIE --}}
@include('partials.landing-pie')

</x-layout>