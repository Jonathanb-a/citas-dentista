<div x-data="{ abierto: false }">

    <nav class="w-full bg-white fixed top-0 left-0 z-50 shadow-sm">
        <div class="flex items-center justify-between px-6 md:px-16 h-[82px] md:h-[100px]">

            {{-- Logo --}}
            <a href="#inicio">
                <img src="{{ asset('img/logo-dentista.png') }}"
                     alt="Melissa López N."
                     class="h-16 md:h-[110px] w-auto object-contain">
            </a>

            {{-- Menu desktop --}}
            <div class="hidden md:flex items-center gap-6 lg:gap-10">
                <a href="#inicio" class="text-[#2c2c2c] text-sm lg:text-base font-medium tracking-wide hover:text-[#f285a2] transition-colors duration-300">INICIO</a>
                <a href="#nosotros" class="text-[#2c2c2c] text-sm lg:text-base font-medium tracking-wide hover:text-[#f285a2] transition-colors duration-300">NOSOTROS</a>
                <a href="#galeria" class="text-[#2c2c2c] text-sm lg:text-base font-medium tracking-wide hover:text-[#f285a2] transition-colors duration-300">GALERÍA</a>
                <a href="#testimonios" class="text-[#2c2c2c] text-sm lg:text-base font-medium tracking-wide hover:text-[#f285a2] transition-colors duration-300">TESTIMONIOS</a>
                <a href="#certificados" class="text-[#2c2c2c] text-sm lg:text-base font-medium tracking-wide hover:text-[#f285a2] transition-colors duration-300">CERTIFICADOS</a>
                <a href="#contacto" class="text-[#2c2c2c] text-sm lg:text-base font-medium tracking-wide hover:text-[#f285a2] transition-colors duration-300">CONTÁCTANOS</a>
            </div>

            {{-- Boton Registrate desktop --}}
            <a href="/login"
               class="hidden md:flex items-center justify-center border border-[#2c2c2c] text-[#2c2c2c] text-sm lg:text-base font-medium px-5 py-2 rounded-md hover:bg-[#f285a2] hover:text-white hover:border-[#f285a2] transition-all duration-300">
                REGÍSTRATE
            </a>

            {{-- Hamburguesa mobile --}}
            <button @click="abierto = !abierto"
                    class="md:hidden p-2 focus:outline-none">
                <svg x-show="!abierto"
                     xmlns="http://www.w3.org/2000/svg"
                     class="w-7 h-7 text-[#2c2c2c]"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg x-show="abierto"
                     xmlns="http://www.w3.org/2000/svg"
                     class="w-7 h-7 text-[#2c2c2c]"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

        </div>
    </nav>

    {{-- Menu mobile desplegable --}}
    <div x-show="abierto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="md:hidden fixed top-[82px] left-0 w-full bg-white px-6 pt-2 pb-6 flex flex-col z-50 shadow-lg">

        <a href="#inicio"
           @click="abierto = false"
           class="text-[#2c2c2c] text-sm font-medium py-3 border-b border-gray-100 hover:text-[#f285a2] transition-colors duration-300">
            INICIO
        </a>
        <a href="#nosotros"
           @click="abierto = false"
           class="text-[#2c2c2c] text-sm font-medium py-3 border-b border-gray-100 hover:text-[#f285a2] transition-colors duration-300">
            NOSOTROS
        </a>
        <a href="#galeria"
           @click="abierto = false"
           class="text-[#2c2c2c] text-sm font-medium py-3 border-b border-gray-100 hover:text-[#f285a2] transition-colors duration-300">
            GALERÍA
        </a>
        <a href="#testimonios"
           @click="abierto = false"
           class="text-[#2c2c2c] text-sm font-medium py-3 border-b border-gray-100 hover:text-[#f285a2] transition-colors duration-300">
            TESTIMONIOS
        </a>
        <a href="#certificados"
           @click="abierto = false"
           class="text-[#2c2c2c] text-sm font-medium py-3 border-b border-gray-100 hover:text-[#f285a2] transition-colors duration-300">
            CERTIFICADOS
        </a>
        <a href="#contacto"
           @click="abierto = false"
           class="text-[#2c2c2c] text-sm font-medium py-3 border-b border-gray-100 hover:text-[#f285a2] transition-colors duration-300">
            CONTÁCTANOS
        </a>
        <a href="/register"
           class="mt-4 border border-[#2c2c2c] text-[#2c2c2c] text-sm font-medium px-6 py-2.5 rounded-md text-center hover:bg-[#f285a2] hover:text-white hover:border-[#f285a2] transition-all duration-300">
            Regístrate
        </a>

    </div>

</div>

{{-- Espacio navbar fijo --}}
<div class="h-[82px] md:h-[100px]"></div>