{{-- Un diente del odontograma. Parámetros: $numero, $arcada ('superior' | 'inferior') --}}
@php
    $tipo = \App\Models\Odontograma::tipo($numero);

    // Corona y raíces de cada tipo de diente. La arcada inferior se voltea.
    $formas = [
        'molar' => ['corona' => 'M6 12 Q6 7 12 7 Q18 7 18 12 L18 22 Q18 26 12 26 Q6 26 6 22 Z', 'raices' => 'M8 26 L7 36 M12 26 L12 37 M16 26 L17 36'],
        'premolar' => ['corona' => 'M7 13 Q7 8 12 8 Q17 8 17 13 L17 22 Q17 26 12 26 Q7 26 7 22 Z', 'raices' => 'M10 26 L9 36 M14 26 L15 36'],
        'canino' => ['corona' => 'M8 14 Q8 7 12 7 Q16 7 16 14 L16 22 Q16 26 12 26 Q8 26 8 22 Z', 'raices' => 'M12 26 L12 38'],
        'incisivo' => ['corona' => 'M8 12 Q8 8 12 8 Q16 8 16 12 L16 22 Q16 26 12 26 Q8 26 8 22 Z', 'raices' => 'M12 26 L12 37'],
    ];
    $forma = $formas[$tipo];
@endphp

<button type="button" @click="abrirDiente({{ $numero }})"
        class="group flex flex-col items-center gap-0.5 rounded-lg p-1 min-w-[44px] focus:outline-none focus:ring-2 focus:ring-[#fde7ed] hover:bg-[#FFF6F6] transition-colors duration-200"
        :aria-label="'Diente {{ $numero }}: ' + etiqueta(dientes[{{ $numero }}].estado)">

    @if($arcada === 'inferior')
        <span class="text-[10px] font-medium text-gray-400 group-hover:text-[#d75078]">{{ $numero }}</span>
    @endif

    <svg viewBox="0 0 24 40" class="w-7 h-11 sm:w-8 sm:h-12" aria-hidden="true"
         @if($arcada === 'inferior') style="transform: scaleY(-1)" @endif>
        {{-- Raíces --}}
        <path d="{{ $forma['raices'] }}" fill="none" stroke-width="2" stroke-linecap="round"
              :stroke="colorBorde(dientes[{{ $numero }}].estado)" />
        {{-- Corona --}}
        <path d="{{ $forma['corona'] }}" stroke-width="1.5"
              :fill="colorRelleno(dientes[{{ $numero }}].estado)"
              :stroke="colorBorde(dientes[{{ $numero }}].estado)" />
        {{-- Diente ausente: además del color, va tachado --}}
        <template x-if="dientes[{{ $numero }}].estado === 'ausente'">
            <g stroke="#111827" stroke-width="2.5" stroke-linecap="round">
                <line x1="4" y1="6" x2="20" y2="28" />
                <line x1="20" y1="6" x2="4" y2="28" />
            </g>
        </template>
        {{-- Punto que indica que el diente tiene nota --}}
        <template x-if="dientes[{{ $numero }}].nota">
            <circle cx="20" cy="33" r="2.5" fill="#d75078" />
        </template>
    </svg>

    @if($arcada === 'superior')
        <span class="text-[10px] font-medium text-gray-400 group-hover:text-[#d75078]">{{ $numero }}</span>
    @endif
</button>
