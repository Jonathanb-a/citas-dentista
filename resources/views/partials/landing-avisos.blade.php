{{-- AVISOS (franja arriba; el margen libra el navbar fijo) --}}
<div data-avisos class="w-full mt-[82px] md:mt-[100px]">
    @foreach($avisos as $aviso)
        @php $paleta = $aviso->paleta(); @endphp
        <div class="w-full {{ $paleta['franja'] }} px-6 md:px-[108px] py-3">
            <div class="flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-5 text-center" style="font-family: 'Montserrat', sans-serif;">
                <p class="text-sm md:text-base">
                    @if($aviso->emoji)<span>{{ $aviso->emoji }}</span>@endif
                    <span class="font-bold">{{ $aviso->titulo }}</span>
                    @if($aviso->descripcion)<span class="font-light"> — {{ $aviso->descripcion }}</span>@endif
                </p>
                @if($aviso->muestraBoton())
                    <a href="{{ $aviso->enlaceBoton() }}"
                       @if($aviso->enlaceEsExterno()) target="_blank" rel="noopener noreferrer" @endif
                       class="inline-block shrink-0 {{ $paleta['boton'] }} text-white text-xs md:text-sm font-medium px-5 py-2 rounded-full transition-all duration-300">
                        {{ $aviso->textoBoton() }}
                    </a>
                @endif
            </div>
        </div>
    @endforeach
</div>
