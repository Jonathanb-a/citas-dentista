<x-layout>

    {{-- HERO --}}
    <section id="inicio" class="w-full min-h-[600px] md:min-h-[719px] bg-[#fde7ed9c] flex items-center px-6 md:px-[108px] py-16 md:py-0">

        {{-- Contenido izquierda --}}
        <div class="flex flex-col gap-4 w-full md:w-1/2">

            {{-- Tag superior --}}
            <span class="font-bold text-[#d75078] text-sm md:text-base tracking-wide" style="font-family: 'Montserrat', sans-serif;">
                SONRISAS QUE ENAMORAN
            </span>

            {{-- Titulo --}}
            <h1 class="font-bold text-gray-800 text-4xl md:text-6xl leading-tight" style="font-family: 'Montserrat', sans-serif;">
                TU MEJOR SONRISA <br> COMIENZA AQUI
            </h1>

            {{-- Descripcion --}}
            <p class="font-light text-gray-800 text-base md:text-xl leading-relaxed max-w-[526px]" style="font-family: 'Montserrat', sans-serif;">
                Tecnología de vanguardia y un trato humano para transformar tu salud dental sin dolor y con resultados increíbles.
            </p>

            {{-- Boton --}}
            <div class="mt-4">
                <a href="#contacto" class="inline-block bg-[#f285a2] text-white font-medium text-sm md:text-base px-8 py-3 rounded-full hover:bg-[#d75078] transition-all duration-300" style="font-family: 'Montserrat', sans-serif;">
                    AGENDAR CITA
                </a>
            </div>

        </div>

        {{-- Imagen derecha --}}
        <div class="hidden md:flex w-1/2 justify-center items-center">
            <img src="{{ asset('img/image 1.png') }}"
                 alt="Dentista"
                 class="w-[579px] h-[423px] object-cover rounded-2xl shadow-lg">
        </div>

    </section>

    {{-- BARRA DE ANUNCIOS --}}
<div class="w-full bg-[#d75078] py-3 overflow-hidden">
    <div class="flex whitespace-nowrap animate-marquee">
        <span class="text-white text-sm font-medium tracking-widest mx-8" style="font-family: 'Montserrat', sans-serif;">
            🦷 LIMPIEZA DENTAL &nbsp;&nbsp;-------&nbsp;&nbsp; 🦷 ORTODONCIA &nbsp;&nbsp;-------&nbsp;&nbsp; 🦷 BLANQUEAMIENTO &nbsp;&nbsp;-------&nbsp;&nbsp; 🦷 IMPLANTES &nbsp;&nbsp;-------&nbsp;&nbsp; 🦷 CONSULTA GRATIS &nbsp;&nbsp;-------&nbsp;&nbsp; 🦷 LIMPIEZA DENTAL &nbsp;&nbsp;-------&nbsp;&nbsp; 🦷 ORTODONCIA &nbsp;&nbsp;-------&nbsp;&nbsp; 🦷 BLANQUEAMIENTO &nbsp;&nbsp;-------&nbsp;&nbsp; 🦷 IMPLANTES &nbsp;&nbsp;-------&nbsp;&nbsp; 🦷 CONSULTA GRATIS &nbsp;&nbsp;-------&nbsp;&nbsp;
        </span>
    </div>
</div>


@if(!empty($anuncios) && $anuncios->isNotEmpty())
    @include('partials.landing-carrusel')
@endif

{{-- MISION Y VISION --}}
<section id="nosotros" class="w-full bg-white py-16 md:py-24 px-6 md:px-[108px]">

    {{-- MISION --}}
    <div class="flex flex-col md:flex-row items-center gap-10 md:gap-16 mb-16 md:mb-24">

        {{-- Texto izquierda --}}
        <div class="w-full md:w-1/2 flex flex-col gap-4">
            <h2 class="font-bold text-gray-800 text-4xl md:text-5xl" style="font-family: 'Montserrat', sans-serif;">
                MISIÓN
            </h2>
            <p class="font-light text-gray-600 text-base md:text-lg leading-relaxed" style="font-family: 'Montserrat', sans-serif;">
                Cuidar y transformar sonrisas a través de servicios odontológicos confiables, éticos y accesibles. Trabajamos con tecnología actual y un trato humano para que cada paciente viva una experiencia segura.
            </p>
        </div>

        {{-- Imagen derecha --}}
        <div class="w-full md:w-1/2 flex justify-end">
            <img src="{{ asset('img/mision.png') }}"
                 alt="Misión"
                 class="w-full md:w-[490px] h-[320px] object-cover rounded-2xl shadow-md">
        </div>

    </div>

    {{-- VISION --}}
    <div class="flex flex-col-reverse md:flex-row items-center gap-10 md:gap-16">

        {{-- Imagen izquierda --}}
        <div class="w-full md:w-1/2 flex justify-start">
            <img src="{{ asset('img/vision.png') }}"
                 alt="Visión"
                 class="w-full md:w-[490px] h-[320px] object-cover rounded-2xl shadow-md">
        </div>

        {{-- Texto derecha --}}
        <div class="w-full md:w-1/2 flex flex-col gap-4">
            <h2 class="font-bold text-gray-800 text-4xl md:text-5xl" style="font-family: 'Montserrat', sans-serif;">
                VISIÓN
            </h2>
            <p class="font-light text-gray-600 text-base md:text-lg leading-relaxed" style="font-family: 'Montserrat', sans-serif;">
                Ser la consultoría dental más confiable y cercana, reconocida por innovar, educar y acompañar a cada persona en el camino hacia una salud bucal plena.
            </p>
        </div>

    </div>

</section>
{{-- TRANSFORMACIONES REALES --}}
<section id="galeria" class="w-full bg-white py-16 md:py-24 overflow-hidden">

    {{-- Titulo --}}
    <h2 class="font-bold text-gray-800 text-3xl md:text-5xl text-center mb-12"
        style="font-family: 'Montserrat', sans-serif;">
        TRASFORMACIONES REALES
    </h2>

    {{-- Carrusel --}}
    <div x-data="{
            actual: 0,
            imagenes: [
                { src: '{{ asset('img/galeria-1.png') }}', titulo: 'Diseño de Sonrisa', subtitulo: 'Tratamiento de carillas' },
                { src: '{{ asset('img/galeria-2.png') }}', titulo: 'Ortodoncia', subtitulo: 'Brackets y alineadores' },
                { src: '{{ asset('img/galeria-3.png') }}', titulo: 'Blanqueamiento', subtitulo: 'Resultados visibles' },
                { src: '{{ asset('img/galeria-4.png') }}', titulo: 'Implantes', subtitulo: 'Solución permanente' },
                { src: '{{ asset('img/galeria-5.png') }}', titulo: 'Limpieza Dental', subtitulo: 'Profunda y sin dolor' }
            ],
            siguiente() { this.actual = (this.actual + 1) % this.imagenes.length },
            anterior() { this.actual = (this.actual - 1 + this.imagenes.length) % this.imagenes.length }
         }"
         class="relative px-6 md:px-[108px]">

        {{-- Imagenes --}}
        <div class="flex gap-6 overflow-hidden">
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

        {{-- Botones navegacion --}}
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

    </div>

</section>

{{-- TESTIMONIOS --}}
<section id="testimonios" class="w-full bg-white py-16 md:py-24">

    {{-- Titulo --}}
    <h2 class="font-bold text-gray-800 text-3xl md:text-5xl text-center mb-16"
        style="font-family: 'Montserrat', sans-serif;">
        TESTIMONIOS
    </h2>

    {{-- Carrusel infinito --}}
    <div class="relative overflow-hidden">

        {{-- Degradado izquierda --}}
        <div class="absolute left-0 top-0 h-full w-32 z-10 pointer-events-none"
             style="background: linear-gradient(to right, white, transparent);"></div>

        {{-- Degradado derecha --}}
        <div class="absolute right-0 top-0 h-full w-32 z-10 pointer-events-none"
             style="background: linear-gradient(to left, white, transparent);"></div>

        {{-- Track --}}
        <div class="flex animate-carousel gap-8 w-max">

            {{-- Set 1 --}}

            {{-- Card 1 --}}
            <div class="flex-none w-[280px] md:w-[340px] flex flex-col items-center gap-3 px-4">
                <img src="{{ asset('img/paciente-1.png') }}" alt="Maria G."
                     class="w-28 h-28 rounded-full object-cover bg-gray-200">
                <p class="font-bold text-[#d75078] text-base"
                   style="font-family: 'Montserrat', sans-serif;">Maria G.</p>
                <span class="text-yellow-400 text-xl">★★★★★</span>
                <p class="font-light text-gray-600 text-sm leading-relaxed text-center"
                   style="font-family: 'Montserrat', sans-serif;">
                    Excelente atención, la doctora fue muy paciente conmigo. Tenía mucho miedo al dentista pero aquí me sentí en casa.
                </p>
            </div>

            {{-- Card 2 --}}
            <div class="flex-none w-[280px] md:w-[340px] flex flex-col items-center gap-3 px-4">
                <img src="{{ asset('img/paciente-2.png') }}" alt="Laura M."
                     class="w-28 h-28 rounded-full object-cover bg-gray-200">
                <p class="font-bold text-[#d75078] text-base"
                   style="font-family: 'Montserrat', sans-serif;">Laura M.</p>
                <span class="text-yellow-400 text-xl">★★★★★</span>
                <p class="font-light text-gray-600 text-sm leading-relaxed text-center"
                   style="font-family: 'Montserrat', sans-serif;">
                    Me hicieron un diseño de sonrisa increíble. El resultado superó mis expectativas, estoy muy feliz con mi cambio.
                </p>
            </div>

            {{-- Card 3 --}}
            <div class="flex-none w-[280px] md:w-[340px] flex flex-col items-center gap-3 px-4">
                <img src="{{ asset('img/paciente-3.png') }}" alt="Ana R."
                     class="w-28 h-28 rounded-full object-cover bg-gray-200">
                <p class="font-bold text-[#d75078] text-base"
                   style="font-family: 'Montserrat', sans-serif;">Ana R.</p>
                <span class="text-yellow-400 text-xl">★★★★★</span>
                <p class="font-light text-gray-600 text-sm leading-relaxed text-center"
                   style="font-family: 'Montserrat', sans-serif;">
                    El ambiente es muy agradable y el equipo muy profesional. Ya llevo 3 citas y siempre salgo contenta.
                </p>
            </div>

            {{-- Card 4 --}}
            <div class="flex-none w-[280px] md:w-[340px] flex flex-col items-center gap-3 px-4">
                <img src="{{ asset('img/paciente-4.png') }}" alt="Sofia P."
                     class="w-28 h-28 rounded-full object-cover bg-gray-200">
                <p class="font-bold text-[#d75078] text-base"
                   style="font-family: 'Montserrat', sans-serif;">Sofia P.</p>
                <span class="text-yellow-400 text-xl">★★★★★</span>
                <p class="font-light text-gray-600 text-sm leading-relaxed text-center"
                   style="font-family: 'Montserrat', sans-serif;">
                    El blanqueamiento quedó perfecto. En una sola sesión noté un cambio enorme, súper recomendada.
                </p>
            </div>

            {{-- Card 5 --}}
            <div class="flex-none w-[280px] md:w-[340px] flex flex-col items-center gap-3 px-4">
                <img src="{{ asset('img/paciente-5.png') }}" alt="Carmen V."
                     class="w-28 h-28 rounded-full object-cover bg-gray-200">
                <p class="font-bold text-[#d75078] text-base"
                   style="font-family: 'Montserrat', sans-serif;">Carmen V.</p>
                <span class="text-yellow-400 text-xl">★★★★★</span>
                <p class="font-light text-gray-600 text-sm leading-relaxed text-center"
                   style="font-family: 'Montserrat', sans-serif;">
                    Llevé a mis hijos y los trataron con mucha paciencia. La mejor clínica dental que he visitado.
                </p>
            </div>

            {{-- Set 2 duplicado para loop infinito --}}

            {{-- Card 1 --}}
            <div class="flex-none w-[280px] md:w-[340px] flex flex-col items-center gap-3 px-4">
                <img src="{{ asset('img/paciente-1.png') }}" alt="Maria G."
                     class="w-28 h-28 rounded-full object-cover bg-gray-200">
                <p class="font-bold text-[#d75078] text-base"
                   style="font-family: 'Montserrat', sans-serif;">Maria G.</p>
                <span class="text-yellow-400 text-xl">★★★★★</span>
                <p class="font-light text-gray-600 text-sm leading-relaxed text-center"
                   style="font-family: 'Montserrat', sans-serif;">
                    Excelente atención, la doctora fue muy paciente conmigo. Tenía mucho miedo al dentista pero aquí me sentí en casa.
                </p>
            </div>

            {{-- Card 2 --}}
            <div class="flex-none w-[280px] md:w-[340px] flex flex-col items-center gap-3 px-4">
                <img src="{{ asset('img/paciente-2.png') }}" alt="Laura M."
                     class="w-28 h-28 rounded-full object-cover bg-gray-200">
                <p class="font-bold text-[#d75078] text-base"
                   style="font-family: 'Montserrat', sans-serif;">Laura M.</p>
                <span class="text-yellow-400 text-xl">★★★★★</span>
                <p class="font-light text-gray-600 text-sm leading-relaxed text-center"
                   style="font-family: 'Montserrat', sans-serif;">
                    Me hicieron un diseño de sonrisa increíble. El resultado superó mis expectativas, estoy muy feliz con mi cambio.
                </p>
            </div>

            {{-- Card 3 --}}
            <div class="flex-none w-[280px] md:w-[340px] flex flex-col items-center gap-3 px-4">
                <img src="{{ asset('img/paciente-3.png') }}" alt="Ana R."
                     class="w-28 h-28 rounded-full object-cover bg-gray-200">
                <p class="font-bold text-[#d75078] text-base"
                   style="font-family: 'Montserrat', sans-serif;">Ana R.</p>
                <span class="text-yellow-400 text-xl">★★★★★</span>
                <p class="font-light text-gray-600 text-sm leading-relaxed text-center"
                   style="font-family: 'Montserrat', sans-serif;">
                    El ambiente es muy agradable y el equipo muy profesional. Ya llevo 3 citas y siempre salgo contenta.
                </p>
            </div>

            {{-- Card 4 --}}
            <div class="flex-none w-[280px] md:w-[340px] flex flex-col items-center gap-3 px-4">
                <img src="{{ asset('img/paciente-4.png') }}" alt="Sofia P."
                     class="w-28 h-28 rounded-full object-cover bg-gray-200">
                <p class="font-bold text-[#d75078] text-base"
                   style="font-family: 'Montserrat', sans-serif;">Sofia P.</p>
                <span class="text-yellow-400 text-xl">★★★★★</span>
                <p class="font-light text-gray-600 text-sm leading-relaxed text-center"
                   style="font-family: 'Montserrat', sans-serif;">
                    El blanqueamiento quedó perfecto. En una sola sesión noté un cambio enorme, súper recomendada.
                </p>
            </div>

            {{-- Card 5 --}}
            <div class="flex-none w-[280px] md:w-[340px] flex flex-col items-center gap-3 px-4">
                <img src="{{ asset('img/paciente-5.png') }}" alt="Carmen V."
                     class="w-28 h-28 rounded-full object-cover bg-gray-200">
                <p class="font-bold text-[#d75078] text-base"
                   style="font-family: 'Montserrat', sans-serif;">Carmen V.</p>
                <span class="text-yellow-400 text-xl">★★★★★</span>
                <p class="font-light text-gray-600 text-sm leading-relaxed text-center"
                   style="font-family: 'Montserrat', sans-serif;">
                    Llevé a mis hijos y los trataron con mucha paciencia. La mejor clínica dental que he visitado.
                </p>
            </div>

        </div>
    </div>

</section>

{{-- CERTIFICADOS --}}
<section id="certificados" class="w-full bg-white py-16 md:py-24 px-6 md:px-[108px]">

    {{-- Titulo --}}
    <h2 class="font-bold text-gray-800 text-3xl md:text-5xl text-center mb-16"
        style="font-family: 'Montserrat', sans-serif;">
        CERTIFICADOS
    </h2>

    {{-- Contenido --}}
    <div class="flex flex-col md:flex-row items-center justify-center gap-10 md:gap-20">

        {{-- Imagen certificado --}}
        <div class="flex flex-col items-center gap-6">
            <img src="{{ asset('img/certificado.png') }}"
                 alt="Certificado"
                 class="w-[280px] md:w-[380px] object-contain drop-shadow-lg hover:scale-105 transition-transform duration-300">
            <p class="font-light text-gray-500 text-sm md:text-base text-center max-w-[500px]"
               style="font-family: 'Montserrat', sans-serif;">
                Certificada por la Asociación Dental Mexicana (ADM) y Especialidad en Ortodoncia.
            </p>
        </div>

    </div>

</section>

{{-- CONTACTO --}}
<section id="contacto" class="w-full bg-white py-16 md:py-24 px-6 md:px-[108px]">

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
                <span class="font-semibold">Horario: 10am - 7pm</span>
            </p>
            <a href="tel:+529991231234"
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
            <a href="/login"
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
            Plaza Marsella, Local 1
        </p>
        <p class="font-light text-gray-500 text-sm md:text-base text-center"
           style="font-family: 'Montserrat', sans-serif;">
            Av. Cámara de Comercio, Mérida, Yucatán
        </p>

        {{-- Mapa --}}
        <div class="w-full md:w-[700px] h-[300px] rounded-2xl overflow-hidden shadow-md mt-4">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3724.5!2d-89.6!3d20.97!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjDCsDU4JzEyLjAiTiA4OcKwMzYnMDAuMCJX!5e0!3m2!1ses!2smx!4v1234567890"
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
            <a href="#testimonios" class="text-gray-500 text-sm hover:text-[#d75078] transition-colors duration-300"
               style="font-family: 'Montserrat', sans-serif;">TESTIMONIOS</a>
            <a href="#certificados" class="text-gray-500 text-sm hover:text-[#d75078] transition-colors duration-300"
               style="font-family: 'Montserrat', sans-serif;">CERTIFICADOS</a>
            <a href="#contacto" class="text-gray-500 text-sm hover:text-[#d75078] transition-colors duration-300"
               style="font-family: 'Montserrat', sans-serif;">CONTÁCTANOS</a>
        </div>

        {{-- Redes sociales --}}
        <div class="flex items-center gap-4">

            {{-- Instagram --}}
            <a href="#" target="_blank"
               class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-[#fde7ed] hover:border-[#d75078] transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500 hover:text-[#d75078]" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
            </a>

            {{-- Facebook --}}
            <a href="#" target="_blank"
               class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-[#fde7ed] hover:border-[#d75078] transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
            </a>

            {{-- WhatsApp --}}
            <a href="https://wa.me/529991231234" target="_blank"
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
            © 2026 Melissa López N. Todos los derechos reservados.
        </p>
        <p class="text-gray-400 text-xs text-center"
           style="font-family: 'Montserrat', sans-serif;">
            Odontología Integral — Mérida, Yucatán
        </p>
    </div>

</footer>

</x-layout>