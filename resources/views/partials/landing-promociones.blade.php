{{-- PROMOCIONES --}}
<section id="promociones" data-promociones class="w-full bg-white py-16 md:py-24 px-6 md:px-[108px]">

    {{-- Titulo --}}
    <h2 class="font-bold text-gray-800 text-3xl md:text-5xl text-center mb-16"
        style="font-family: 'Montserrat', sans-serif;">
        PROMOCIONES
    </h2>

    {{-- Cards --}}
    <div class="flex flex-col md:flex-row md:flex-wrap items-center md:items-stretch justify-center gap-6 md:gap-10">
        @foreach($promociones as $promocion)
            @php $paleta = $promocion->paleta(); @endphp
            <div class="w-full md:w-[320px] flex flex-col items-center gap-4 p-8 rounded-2xl border {{ $paleta['borde'] }} hover:shadow-lg transition-shadow duration-300">
                <div class="w-16 h-16 {{ $paleta['circulo'] }} rounded-full flex items-center justify-center">
                    @if($promocion->emoji)
                        <span class="text-3xl leading-none">{{ $promocion->emoji }}</span>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 {{ $paleta['acento'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    @endif
                </div>
                <h3 class="font-bold text-gray-800 text-lg text-center"
                    style="font-family: 'Montserrat', sans-serif;">{{ $promocion->titulo }}</h3>
                @if($promocion->descripcion)
                    <p class="font-light text-gray-500 text-sm text-center"
                       style="font-family: 'Montserrat', sans-serif;">{{ $promocion->descripcion }}</p>
                @endif
                @if($promocion->fecha_fin)
                    <p class="font-semibold text-gray-500 text-xs text-center"
                       style="font-family: 'Montserrat', sans-serif;">Válida hasta el {{ $promocion->fecha_fin->format('d/m/Y') }}</p>
                @endif
                <a href="{{ $promocion->enlaceBoton() }}"
                   @if($promocion->enlaceEsExterno()) target="_blank" rel="noopener noreferrer" @endif
                   class="mt-auto w-full text-center {{ $paleta['boton'] }} text-white text-sm font-medium px-6 py-3 rounded-full transition-all duration-300"
                   style="font-family: 'Montserrat', sans-serif;">
                    {{ $promocion->textoBoton() }}
                </a>
            </div>
        @endforeach
    </div>

</section>
