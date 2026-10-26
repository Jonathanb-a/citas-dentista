<x-layout>

    {{-- HERO --}}
    @php
        $focoHero = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#a13153]';
        $botonHero = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl bg-[#c03a63] px-7 py-3.5 text-sm md:text-base font-semibold text-white hover:bg-[#a13153] transition-[background-color] duration-300 ' . $focoHero;
    @endphp
    <section id="inicio" class="w-full min-h-[600px] md:min-h-[719px] bg-linear-to-br from-[#FFF6F6] to-[#fde7ed] flex items-center px-6 md:px-[108px] pt-28 pb-16 md:pt-28 md:pb-16">
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

{{-- MISION Y VISION --}}
<section id="nosotros" data-aparecer class="w-full bg-white py-16 md:py-24 px-6 md:px-[108px]">

    {{-- MISION --}}
    <div class="flex flex-col md:flex-row items-center gap-10 md:gap-16 mb-16 md:mb-24">

        {{-- Texto izquierda --}}
        <div class="w-full md:w-1/2 flex flex-col gap-4">
            <h2 class="font-bold text-gray-800 text-4xl md:text-5xl" style="font-family: 'Montserrat', sans-serif;">
                MISIÓN
            </h2>
            <p class="font-light text-gray-600 text-base md:text-lg leading-relaxed" style="font-family: 'Montserrat', sans-serif;">
                {{ $landing->texto('nosotros.mision_texto') }}
            </p>
        </div>

        {{-- Imagen derecha --}}
        <div class="w-full md:w-1/2 flex justify-end">
            <img src="{{ $landing->imagen('nosotros.mision_imagen') }}"
                 alt="Misión"
                 class="w-full md:w-[490px] h-[320px] object-cover rounded-2xl shadow-md">
        </div>

    </div>

    {{-- VISION --}}
    <div class="flex flex-col-reverse md:flex-row items-center gap-10 md:gap-16">

        {{-- Imagen izquierda --}}
        <div class="w-full md:w-1/2 flex justify-start">
            <img src="{{ $landing->imagen('nosotros.vision_imagen') }}"
                 alt="Visión"
                 class="w-full md:w-[490px] h-[320px] object-cover rounded-2xl shadow-md">
        </div>

        {{-- Texto derecha --}}
        <div class="w-full md:w-1/2 flex flex-col gap-4">
            <h2 class="font-bold text-gray-800 text-4xl md:text-5xl" style="font-family: 'Montserrat', sans-serif;">
                VISIÓN
            </h2>
            <p class="font-light text-gray-600 text-base md:text-lg leading-relaxed" style="font-family: 'Montserrat', sans-serif;">
                {{ $landing->texto('nosotros.vision_texto') }}
            </p>
        </div>

    </div>

</section>
{{-- TRANSFORMACIONES REALES --}}
<section id="galeria" data-aparecer class="w-full bg-white py-16 md:py-24 overflow-hidden">

    {{-- Titulo --}}
    <h2 class="font-bold text-gray-800 text-3xl md:text-5xl text-center mb-12"
        style="font-family: 'Montserrat', sans-serif;">
        {{ $landing->texto('galeria.titulo') }}
    </h2>

    {{-- Carrusel --}}
    @php
        // Texto dentro de comillas simples de JavaScript (después Blade lo escapa para el atributo HTML)
        $js = fn ($valor) => str_replace(['\\', "'", "\r", "\n"], ['\\\\', "\\'", '', ' '], (string) $valor);
    @endphp
    <div x-data="{
            actual: 0,
            imagenes: [
                @foreach($galeria as $foto)
                { src: '{{ $foto['src'] }}', titulo: '{{ $js($foto['titulo']) }}', subtitulo: '{{ $js($foto['subtitulo']) }}' }{{ $loop->last ? '' : ',' }}
                @endforeach
            ],
            siguiente() { this.actual = (this.actual + 1) % this.imagenes.length },
            anterior() { this.actual = (this.actual - 1 + this.imagenes.length) % this.imagenes.length }
         }"
         class="relative px-6 md:px-[108px]">

        {{-- Imagenes --}}
        <div @class(['flex gap-6 overflow-hidden', 'justify-center' => count($galeria) < 3])>
            <template x-for="(imagen, index) in imagenes" :key="index">
                <div class="flex-none w-full md:w-1/3 transition-all duration-500"
                     x-show="index === actual || index === (actual + 1) % imagenes.length || index === (actual + 2) % imagenes.length">

                    <div class="flex flex-col gap-3 cursor-pointer" @click="siguiente()">
                        <img :src="imagen.src"
                             :alt="imagen.titulo"
                             class="w-full h-[300px] md:h-[350px] object-cover rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300">
                        <div class="px-2">
                            <p class="font-bold text-gray-800 text-base" style="font-family: 'Montserrat', sans-serif;" x-text="imagen.titulo"></p>
                            <p class="font-light text-gray-500 text-sm" style="font-family: 'Montserrat', sans-serif;" x-text="imagen.subtitulo"></p>
                        </div>
                    </div>

                </div>
            </template>
        </div>

        {{-- Botones navegacion (con 3 fotos o menos se ven todas a la vez: no hay nada que pasar) --}}
        @if(count($galeria) > 3)
        <div class="flex items-center justify-center gap-4 mt-10">

            {{-- Anterior --}}
            <button @click="anterior()"
                    class="w-10 h-10 rounded-full border border-gray-300 flex items-center justify-center hover:bg-[#f285a2] hover:border-[#f285a2] hover:text-white transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            {{-- Puntos --}}
            <template x-for="(imagen, index) in imagenes" :key="index">
                <button @click="actual = index"
                        class="w-2.5 h-2.5 rounded-full transition-all duration-300"
                        :class="actual === index ? 'bg-[#d75078] w-6' : 'bg-gray-300'">
                </button>
            </template>

            {{-- Siguiente --}}
            <button @click="siguiente()"
                    class="w-10 h-10 rounded-full border border-gray-300 flex items-center justify-center hover:bg-[#f285a2] hover:border-[#f285a2] hover:text-white transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

        </div>
        @endif

    </div>

</section>

{{-- CERTIFICADOS --}}
<section id="certificados" data-aparecer class="w-full bg-white py-16 md:py-24 px-6 md:px-[108px]">

    {{-- Titulo --}}
    <h2 class="font-bold text-gray-800 text-3xl md:text-5xl text-center mb-16"
        style="font-family: 'Montserrat', sans-serif;">
        CERTIFICADOS
    </h2>

    {{-- Contenido --}}
    <div class="flex flex-col md:flex-row items-center justify-center gap-10 md:gap-20">

        {{-- Imagen certificado --}}
        <div class="flex flex-col items-center gap-6">
            <img src="{{ $landing->imagen('certificados.imagen') }}"
                 alt="Certificado"
                 class="w-[280px] md:w-[380px] object-contain drop-shadow-lg hover:scale-105 transition-transform duration-300">
            <p class="font-light text-gray-500 text-sm md:text-base text-center max-w-[500px]"
               style="font-family: 'Montserrat', sans-serif;">
                {{ $landing->texto('certificados.texto') }}
            </p>
        </div>

    </div>

</section>

{{-- CONTACTO --}}
<section id="contacto" data-aparecer class="w-full bg-white py-16 md:py-24 px-6 md:px-[108px]">

    {{-- Titulo --}}
    <h2 class="font-bold text-gray-800 text-2xl md:text-4xl text-center"
        style="font-family: 'Montserrat', sans-serif;">
        ¿NECESITAS UNA CITA?
    </h2>
    <p class="text-gray-500 text-sm md:text-base text-center mt-3 mb-16"
       style="font-family: 'Montserrat', sans-serif;">
        Sabemos que la tecnología a veces es complicada.<br>
        <span class="font-bold text-gray-700">Elige la opción más fácil para ti:</span>
    </p>

    {{-- Cards --}}
    <div class="flex flex-col md:flex-row items-center justify-center gap-6 md:gap-10">

        {{-- Llamar por Telefono --}}
        <div class="w-full md:w-[280px] flex flex-col items-center gap-4 p-8 rounded-2xl border border-blue-100 hover:shadow-lg transition-shadow duration-300">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <h3 class="font-bold text-gray-800 text-lg text-center"
                style="font-family: 'Montserrat', sans-serif;">Llamar por Teléfono</h3>
            <p class="font-light text-gray-500 text-sm text-center"
               style="font-family: 'Montserrat', sans-serif;">
                Habla directamente con nuestra asistente.<br>
                <span class="font-semibold">Horario: {{ $landing->texto('contacto.horario') }}</span>
            </p>
            <a href="{{ $landing->enlaceTelefono() }}"
               class="w-full text-center bg-blue-500 text-white text-sm font-medium px-6 py-3 rounded-full hover:bg-blue-600 transition-all duration-300"
               style="font-family: 'Montserrat', sans-serif;">
                Tocar para llamar
            </a>
        </div>

    
        {{-- Portal de Citas --}}
        <div class="w-full md:w-[280px] flex flex-col items-center gap-4 p-8 rounded-2xl border border-[#fde7ed] hover:shadow-lg transition-shadow duration-300">
            <div class="w-16 h-16 bg-[#fde7ed] rounded-full flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-[#d75078]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="font-bold text-gray-800 text-lg text-center"
                style="font-family: 'Montserrat', sans-serif;">Agendar en el Portal</h3>
            <p class="font-light text-gray-500 text-sm text-center"
               style="font-family: 'Montserrat', sans-serif;">
                Agenda tu cita en línea desde nuestro portal.<br>
                <span class="font-semibold">Rápido y sin llamadas.</span>
            </p>
            <a href="{{ route('paciente.citas.create') }}"
               class="w-full text-center bg-[#d75078] text-white text-sm font-medium px-6 py-3 rounded-full hover:bg-[#f285a2] transition-all duration-300"
               style="font-family: 'Montserrat', sans-serif;">
                Agendar mi cita
            </a>
        </div>

    </div>

    {{-- Donde estamos --}}
    <div class="mt-20 flex flex-col items-center gap-3">
        <h3 class="font-bold text-gray-800 text-lg md:text-xl text-center"
            style="font-family: 'Montserrat', sans-serif;">
            ¿DÓNDE ESTAMOS?
        </h3>
        <p class="font-bold text-gray-700 text-sm md:text-base text-center"
           style="font-family: 'Montserrat', sans-serif;">
            {{ $landing->texto('contacto.direccion1') }}
        </p>
        <p class="font-light text-gray-500 text-sm md:text-base text-center"
           style="font-family: 'Montserrat', sans-serif;">
            {{ $landing->texto('contacto.direccion2') }}
        </p>

        {{-- Mapa --}}
        <div class="w-full md:w-[700px] h-[300px] rounded-2xl overflow-hidden shadow-md mt-4">
            <iframe
                src="{{ $landing->texto('contacto.mapa') }}"
                width="100%"
                height="100%"
                style="border:0;"
                allowfullscreen=""
                loading="lazy">
            </iframe>
        </div>
    </div>

</section>

{{-- FOOTER --}}
<footer class="w-full bg-white border-t border-gray-100 py-10 px-6 md:px-[108px]">

    <div class="flex flex-col md:flex-row items-center justify-between gap-6">

        {{-- Logo --}}
        <div class="flex flex-col items-center md:items-start gap-2">
            <img src="{{ asset('img/logo-dentista.png') }}"
                 alt="Melissa López N."
                 class="h-20 w-auto object-contain">
        </div>

        {{-- Links --}}
        <div class="flex flex-col md:flex-row items-center gap-4 md:gap-10">
            <a href="#inicio" class="text-gray-500 text-sm hover:text-[#d75078] transition-colors duration-300"
               style="font-family: 'Montserrat', sans-serif;">INICIO</a>
            <a href="#nosotros" class="text-gray-500 text-sm hover:text-[#d75078] transition-colors duration-300"
               style="font-family: 'Montserrat', sans-serif;">NOSOTROS</a>
            <a href="#galeria" class="text-gray-500 text-sm hover:text-[#d75078] transition-colors duration-300"
               style="font-family: 'Montserrat', sans-serif;">GALERÍA</a>
            <a href="#certificados" class="text-gray-500 text-sm hover:text-[#d75078] transition-colors duration-300"
               style="font-family: 'Montserrat', sans-serif;">CERTIFICADOS</a>
            <a href="#contacto" class="text-gray-500 text-sm hover:text-[#d75078] transition-colors duration-300"
               style="font-family: 'Montserrat', sans-serif;">CONTÁCTANOS</a>
        </div>

        {{-- Redes sociales --}}
        <div class="flex items-center gap-4">

            {{-- Instagram --}}
            <a href="{{ $landing->texto('contacto.instagram') }}" target="_blank"
               class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-[#fde7ed] hover:border-[#d75078] transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500 hover:text-[#d75078]" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
            </a>

            {{-- Facebook --}}
            <a href="{{ $landing->texto('contacto.facebook') }}" target="_blank"
               class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-[#fde7ed] hover:border-[#d75078] transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
            </a>

            {{-- WhatsApp --}}
            <a href="{{ $landing->enlaceWhatsapp() }}" target="_blank"
               class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-[#fde7ed] hover:border-[#d75078] transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
            </a>

        </div>

    </div>

    {{-- Linea divisora --}}
    <div class="border-t border-gray-100 mt-8 pt-6 flex flex-col md:flex-row items-center justify-between gap-2">
        <p class="text-gray-400 text-xs text-center"
           style="font-family: 'Montserrat', sans-serif;">
            {{ $landing->texto('pie.derechos') }}
        </p>
        <p class="text-gray-400 text-xs text-center"
           style="font-family: 'Montserrat', sans-serif;">
            {{ $landing->texto('pie.ubicacion') }}
        </p>
    </div>

</footer>

</x-layout>