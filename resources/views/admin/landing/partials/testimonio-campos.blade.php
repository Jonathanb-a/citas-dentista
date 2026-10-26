{{-- Campos de una opinión según su tipo: $definicion, $prefijo (para los id), $valores, $errores, $estiloCampo --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    @foreach($definicion['campos'] as $campo => $datosCampo)
        @php
            $tipo = $datosCampo['tipo'] ?? 'texto';
            $idCampo = $prefijo . '-' . $campo;
            $idError = $idCampo . '-error';
            $conError = $errores->has($campo);
        @endphp

        @if($tipo === 'casilla')
            <div class="sm:col-span-2 flex flex-col gap-1.5">
                <label for="{{ $idCampo }}" class="flex items-start gap-3 min-h-11 cursor-pointer">
                    <input type="checkbox" id="{{ $idCampo }}" name="{{ $campo }}" value="1"
                           @checked($valores[$campo] ?? false) @required($datosCampo['obligatoria'] ?? false)
                           @if($conError) aria-invalid="true" aria-describedby="{{ $idError }}" @endif
                           class="mt-0.5 h-5 w-5 shrink-0 accent-[#d75078]">
                    <span class="text-gray-700 text-sm font-medium">
                        {{ $datosCampo['etiqueta'] }}
                        @if($datosCampo['obligatoria'] ?? false)<span class="text-gray-400 font-normal">(obligatoria)</span>@endif
                    </span>
                </label>
                @if($conError) <p id="{{ $idError }}" class="text-red-500 text-xs">{{ $errores->first($campo) }}</p> @endif
            </div>
        @elseif($tipo === 'calificacion')
            <div class="flex flex-col gap-1.5">
                <label for="{{ $idCampo }}" class="text-gray-600 text-sm font-medium">{{ $datosCampo['etiqueta'] }}</label>
                <select id="{{ $idCampo }}" name="{{ $campo }}" required
                        @if($conError) aria-invalid="true" aria-describedby="{{ $idError }}" @endif
                        class="{{ $estiloCampo }}">
                    <option value="" @selected(!($valores[$campo] ?? null))>Elige…</option>
                    @for($estrellas = 5; $estrellas >= 1; $estrellas--)
                        <option value="{{ $estrellas }}" @selected((int) ($valores[$campo] ?? 0) === $estrellas)>
                            {{ str_repeat('★', $estrellas) . str_repeat('☆', 5 - $estrellas) }} ({{ $estrellas }} de 5)
                        </option>
                    @endfor
                </select>
                @if($conError) <p id="{{ $idError }}" class="text-red-500 text-xs">{{ $errores->first($campo) }}</p> @endif
            </div>
        @else
            {{-- Textos: los largos (más de 80 caracteres) en un área de varias líneas --}}
            @php $largo = $datosCampo['max'] > 80; @endphp
            <div class="flex flex-col gap-1.5 {{ $largo ? 'sm:col-span-2' : '' }}">
                <label for="{{ $idCampo }}" class="text-gray-600 text-sm font-medium">{{ $datosCampo['etiqueta'] }}</label>
                @if($largo)
                    <textarea id="{{ $idCampo }}" name="{{ $campo }}" maxlength="{{ $datosCampo['max'] }}" rows="3"
                              @required($datosCampo['requerido'] ?? false)
                              @if($conError) aria-invalid="true" aria-describedby="{{ $idError }}" @endif
                              class="{{ $estiloCampo }}">{{ $valores[$campo] ?? '' }}</textarea>
                @else
                    <input type="text" id="{{ $idCampo }}" name="{{ $campo }}" maxlength="{{ $datosCampo['max'] }}"
                           value="{{ $valores[$campo] ?? '' }}" @required($datosCampo['requerido'] ?? false)
                           @if($conError) aria-invalid="true" aria-describedby="{{ $idError }}" @endif
                           class="{{ $estiloCampo }}">
                @endif
                @if(!empty($datosCampo['ayuda'])) <p class="text-gray-400 text-xs">{{ $datosCampo['ayuda'] }}</p> @endif
                @if($conError) <p id="{{ $idError }}" class="text-red-500 text-xs">{{ $errores->first($campo) }}</p> @endif
            </div>
        @endif
    @endforeach
</div>
