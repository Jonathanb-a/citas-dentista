{{-- Odontograma en solo lectura: se dibuja desde el servidor, sin clic para editar --}}
@php
    use App\Models\Odontograma;

    $formas = [
        'molar' => ['corona' => 'M6 12 Q6 7 12 7 Q18 7 18 12 L18 22 Q18 26 12 26 Q6 26 6 22 Z', 'raices' => 'M8 26 L7 36 M12 26 L12 37 M16 26 L17 36'],
        'premolar' => ['corona' => 'M7 13 Q7 8 12 8 Q17 8 17 13 L17 22 Q17 26 12 26 Q7 26 7 22 Z', 'raices' => 'M10 26 L9 36 M14 26 L15 36'],
        'canino' => ['corona' => 'M8 14 Q8 7 12 7 Q16 7 16 14 L16 22 Q16 26 12 26 Q8 26 8 22 Z', 'raices' => 'M12 26 L12 38'],
        'incisivo' => ['corona' => 'M8 12 Q8 8 12 8 Q16 8 16 12 L16 22 Q16 26 12 26 Q8 26 8 22 Z', 'raices' => 'M12 26 L12 37'],
    ];

    $estadosPresentes = $dientesMarcados->pluck('estado')->unique();
@endphp

<div class="bg-[#FFF6F6] rounded-2xl p-3 sm:p-5 flex flex-col gap-4 overflow-hidden">
    @foreach([
        'ARCADA SUPERIOR' => ['superior_derecho', 'superior_izquierdo'],
        'ARCADA INFERIOR' => ['inferior_derecho', 'inferior_izquierdo'],
    ] as $titulo => $cuadrantes)
        <div>
            @if($loop->first)
                <p class="text-gray-400 text-xs font-medium mb-2 text-center">{{ $titulo }}</p>
            @endif

            <div class="grid grid-cols-8 md:grid-cols-16 gap-x-0.5 gap-y-2 justify-items-center">
                @foreach($cuadrantes as $indice => $cuadrante)
                    @foreach(Odontograma::CUADRANTES[$cuadrante] as $numero)
                        @php
                            $registro = $dientesMarcados->get($numero);
                            $estado = $registro->estado ?? 'sano';
                            $datos = Odontograma::ESTADOS[$estado];
                            $forma = $formas[Odontograma::tipo($numero)];
                            $arcadaInferior = str_starts_with($cuadrante, 'inferior');
                        @endphp

                        <div class="flex flex-col items-center gap-0.5 p-1 {{ $loop->first && $indice === 1 ? 'md:ml-3' : '' }}"
                             title="Diente {{ $numero }}: {{ $datos['etiqueta'] }}{{ $registro?->nota ? ' — ' . $registro->nota : '' }}">
                            @if($arcadaInferior)
                                <span class="text-[10px] font-medium text-gray-400">{{ $numero }}</span>
                            @endif

                            <svg viewBox="0 0 24 40" class="w-7 h-11 sm:w-8 sm:h-12" aria-hidden="true"
                                 @if($arcadaInferior) style="transform: scaleY(-1)" @endif>
                                <path d="{{ $forma['raices'] }}" fill="none" stroke-width="2" stroke-linecap="round" stroke="{{ $datos['borde'] }}"/>
                                <path d="{{ $forma['corona'] }}" stroke-width="1.5" fill="{{ $datos['relleno'] }}" stroke="{{ $datos['borde'] }}"/>
                                @if($estado === 'ausente')
                                    <g stroke="#111827" stroke-width="2.5" stroke-linecap="round">
                                        <line x1="4" y1="6" x2="20" y2="28"/>
                                        <line x1="20" y1="6" x2="4" y2="28"/>
                                    </g>
                                @endif
                            </svg>

                            @if(!$arcadaInferior)
                                <span class="text-[10px] font-medium text-gray-400">{{ $numero }}</span>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>

            @if($loop->last)
                <p class="text-gray-400 text-xs font-medium mt-2 text-center">{{ $titulo }}</p>
            @endif
        </div>

        @if($loop->first)
            <div class="border-t border-dashed border-gray-200"></div>
        @endif
    @endforeach
</div>

{{-- Leyenda: solo los estados que este paciente tiene, más "sano" --}}
<div class="flex flex-wrap gap-x-4 gap-y-2 mt-4">
    @foreach(Odontograma::ESTADOS as $clave => $estado)
        @if($clave === 'sano' || $estadosPresentes->contains($clave))
            <span class="flex items-center gap-2 text-gray-500 text-xs">
                <span class="w-4 h-4 rounded border shrink-0" style="background-color: {{ $estado['relleno'] }}; border-color: {{ $estado['borde'] }}"></span>
                {{ $estado['etiqueta'] }}{{ $clave === 'ausente' ? ' (tachado)' : '' }}
            </span>
        @endif
    @endforeach
</div>

{{-- Detalle de los dientes marcados --}}
@if($dientesMarcados->isNotEmpty())
    <div class="mt-5 pt-5 border-t border-gray-50 flex flex-col gap-3">
        @foreach($dientesMarcados->sortBy('numero_diente') as $diente)
            <div class="flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-3">
                <span class="text-gray-700 text-sm font-medium shrink-0">
                    Diente {{ $diente->numero_diente }} · {{ Odontograma::etiqueta($diente->estado) }}
                </span>
                @if($diente->nota)
                    <span class="text-gray-500 text-sm">{{ $diente->nota }}</span>
                @endif
            </div>
        @endforeach
    </div>
@endif
