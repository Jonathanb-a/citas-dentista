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
<section id="galeria" class="w-full bg-white py-16 md:py-24">

    {{-- Titulo --}}
    <h2 class="font-bold text-gray-800 text-3xl md:text-5xl text-center mb-12"
        style="font-family: 'Montserrat', sans-serif;">
        TRASFORMACIONES REALES
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
        <div class="flex animate-carousel gap-6 w-max">

            {{-- Set 1 --}}
            <div class="flex-none w-[280px] md:w-[380px]">
                <div class="overflow-hidden rounded-xl shadow-md">
                    <img src="{{ asset('img/galeria-1.png') }}" alt="Diseño de Sonrisa"
                         class="w-full h-[240px] md:h-[300px] object-cover">
                </div>
                <div class="mt-3 px-1">
                    <p class="font-bold text-gray-800 text-base" style="font-family: 'Montserrat', sans-serif;">Diseño de Sonrisa</p>
                    <p class="font-light text-gray-500 text-sm" style="font-family: 'Montserrat', sans-serif;">Tratamiento de carillas</p>
                </div>
            </div>

            <div class="flex-none w-[280px] md:w-[380px]">
                <div class="overflow-hidden rounded-xl shadow-md">
                    <img src="{{ asset('img/galeria-2.png') }}" alt="Ortodoncia"
                         class="w-full h-[240px] md:h-[300px] object-cover">
                </div>
                <div class="mt-3 px-1">
                    <p class="font-bold text-gray-800 text-base" style="font-family: 'Montserrat', sans-serif;">Ortodoncia</p>
                    <p class="font-light text-gray-500 text-sm" style="font-family: 'Montserrat', sans-serif;">Brackets y alineadores</p>
                </div>
            </div>

            <div class="flex-none w-[280px] md:w-[380px]">
                <div class="overflow-hidden rounded-xl shadow-md">
                    <img src="{{ asset('img/galeria-3.png') }}" alt="Blanqueamiento"
                         class="w-full h-[240px] md:h-[300px] object-cover">
                </div>
                <div class="mt-3 px-1">
                    <p class="font-bold text-gray-800 text-base" style="font-family: 'Montserrat', sans-serif;">Blanqueamiento</p>
                    <p class="font-light text-gray-500 text-sm" style="font-family: 'Montserrat', sans-serif;">Resultados visibles</p>
                </div>
            </div>

            <div class="flex-none w-[280px] md:w-[380px]">
                <div class="overflow-hidden rounded-xl shadow-md">
                    <img src="{{ asset('img/galeria-4.png') }}" alt="Implantes"
                         class="w-full h-[240px] md:h-[300px] object-cover">
                </div>
                <div class="mt-3 px-1">
                    <p class="font-bold text-gray-800 text-base" style="font-family: 'Montserrat', sans-serif;">Implantes</p>
                    <p class="font-light text-gray-500 text-sm" style="font-family: 'Montserrat', sans-serif;">Solución permanente</p>
                </div>
            </div>

            <div class="flex-none w-[280px] md:w-[380px]">
                <div class="overflow-hidden rounded-xl shadow-md">
                    <img src="{{ asset('img/galeria-5.png') }}" alt="Limpieza Dental"
                         class="w-full h-[240px] md:h-[300px] object-cover">
                </div>
                <div class="mt-3 px-1">
                    <p class="font-bold text-gray-800 text-base" style="font-family: 'Montserrat', sans-serif;">Limpieza Dental</p>
                    <p class="font-light text-gray-500 text-sm" style="font-family: 'Montserrat', sans-serif;">Profunda y sin dolor</p>
                </div>
            </div>

            {{-- Set 2 duplicado para loop infinito --}}
            <div class="flex-none w-[280px] md:w-[380px]">
                <div class="overflow-hidden rounded-xl shadow-md">
                    <img src="{{ asset('img/galeria-1.png') }}" alt="Diseño de Sonrisa"
                         class="w-full h-[240px] md:h-[300px] object-cover">
                </div>
                <div class="mt-3 px-1">
                    <p class="font-bold text-gray-800 text-base" style="font-family: 'Montserrat', sans-serif;">Diseño de Sonrisa</p>
                    <p class="font-light text-gray-500 text-sm" style="font-family: 'Montserrat', sans-serif;">Tratamiento de carillas</p>
                </div>
            </div>

            <div class="flex-none w-[280px] md:w-[380px]">
                <div class="overflow-hidden rounded-xl shadow-md">
                    <img src="{{ asset('img/galeria-2.png') }}" alt="Ortodoncia"
                         class="w-full h-[240px] md:h-[300px] object-cover">
                </div>
                <div class="mt-3 px-1">
                    <p class="font-bold text-gray-800 text-base" style="font-family: 'Montserrat', sans-serif;">Ortodoncia</p>
                    <p class="font-light text-gray-500 text-sm" style="font-family: 'Montserrat', sans-serif;">Brackets y alineadores</p>
                </div>
            </div>

            <div class="flex-none w-[280px] md:w-[380px]">
                <div class="overflow-hidden rounded-xl shadow-md">
                    <img src="{{ asset('img/galeria-3.png') }}" alt="Blanqueamiento"
                         class="w-full h-[240px] md:h-[300px] object-cover">
                </div>
                <div class="mt-3 px-1">
                    <p class="font-bold text-gray-800 text-base" style="font-family: 'Montserrat', sans-serif;">Blanqueamiento</p>
                    <p class="font-light text-gray-500 text-sm" style="font-family: 'Montserrat', sans-serif;">Resultados visibles</p>
                </div>
            </div>

            <div class="flex-none w-[280px] md:w-[380px]">
                <div class="overflow-hidden rounded-xl shadow-md">
                    <img src="{{ asset('img/galeria-4.png') }}" alt="Implantes"
                         class="w-full h-[240px] md:h-[300px] object-cover">
                </div>
                <div class="mt-3 px-1">
                    <p class="font-bold text-gray-800 text-base" style="font-family: 'Montserrat', sans-serif;">Implantes</p>
                    <p class="font-light text-gray-500 text-sm" style="font-family: 'Montserrat', sans-serif;">Solución permanente</p>
                </div>
            </div>

            <div class="flex-none w-[280px] md:w-[380px]">
                <div class="overflow-hidden rounded-xl shadow-md">
                    <img src="{{ asset('img/galeria-5.png') }}" alt="Limpieza Dental"
                         class="w-full h-[240px] md:h-[300px] object-cover">
                </div>
                <div class="mt-3 px-1">
                    <p class="font-bold text-gray-800 text-base" style="font-family: 'Montserrat', sans-serif;">Limpieza Dental</p>
                    <p class="font-light text-gray-500 text-sm" style="font-family: 'Montserrat', sans-serif;">Profunda y sin dolor</p>
                </div>
            </div>

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

</x-layout>