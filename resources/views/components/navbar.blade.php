<div x-data="{ abierto: false }">

    <nav class="w-full h-[82px] bg-[#f2f2f2] flex items-center justify-between px-6 md:px-16 fixed top-0 left-0 z-50">

        {{-- Logo --}}
        <div class="flex items-center">
            <img src="{{ asset('img/logo-dentista.png') }}" alt="Dentissa Logo" class="h-16 md:h-28 w-auto object-contain mt-1 md:mt-3">
        </div>

        {{-- Menu desktop --}}
        <div class="hidden md:flex items-center gap-10">
            <a href="#home" class="text-black text-base hover:text-[#f285a2] transition-colors duration-300">HOME</a>
            <a href="#quienes-somos" class="text-black text-base hover:text-[#f285a2] transition-colors duration-300">QUIÉNES SOMOS</a>
            <a href="#servicios" class="text-black text-base hover:text-[#f285a2] transition-colors duration-300">SERVICIOS</a>
            <a href="#galeria" class="text-black text-base hover:text-[#f285a2] transition-colors duration-300">GALERÍA</a>
            <a href="#agendar" class="text-black text-base hover:text-[#f285a2] transition-colors duration-300">AGENDAR</a>
        </div>

        {{-- Boton desktop --}}
        <a href="/register" class="hidden md:block border border-black text-black text-base px-6 py-2 rounded-md hover:bg-[#f285a2] hover:text-white hover:border-[#f285a2] transition-all duration-300">
            REGÍSTRATE
        </a>

        {{-- Boton hamburguesa mobile --}}
        <button @click="abierto = !abierto" class="md:hidden p-2 focus:outline-none">
            <svg x-show="!abierto" xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <svg x-show="abierto" xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

    </nav>

    {{-- Menu mobile desplegable --}}
    <div
        x-show="abierto"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="md:hidden fixed top-[82px] left-0 w-full bg-[#f2f2f2] px-6 pb-6 flex flex-col gap-3 z-50 shadow-md"
    >
        <a href="#home" class="text-black text-sm py-2 border-b border-gray-200 hover:text-[#f285a2] transition-colors duration-300">HOME</a>
        <a href="#quienes-somos" class="text-black text-sm py-2 border-b border-gray-200 hover:text-[#f285a2] transition-colors duration-300">QUIÉNES SOMOS</a>
        <a href="#servicios" class="text-black text-sm py-2 border-b border-gray-200 hover:text-[#f285a2] transition-colors duration-300">SERVICIOS</a>
        <a href="#galeria" class="text-black text-sm py-2 border-b border-gray-200 hover:text-[#f285a2] transition-colors duration-300">GALERÍA</a>
        <a href="#agendar" class="text-black text-sm py-2 border-b border-gray-200 hover:text-[#f285a2] transition-colors duration-300">AGENDAR</a>
        <a href="/register" class="mt-2 border border-black text-black text-sm px-6 py-2 rounded-md text-center hover:bg-[#f285a2] hover:text-white hover:border-[#f285a2] transition-all duration-300">
            REGÍSTRATE
        </a>
    </div>

</div>

{{-- Espacio para que el contenido no quede debajo del navbar fijo --}}
<div class="h-[82px]"></div>