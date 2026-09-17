{{-- Selector Masculino / Femenino con estilo de botones. Parámetros: $nombre, $valor, $requerido (opcional) --}}
@php
    $requerido = $requerido ?? false;
    $conError = $errors->has($nombre);
@endphp
<div class="grid grid-cols-2 gap-3" role="radiogroup">
    @foreach(['masculino' => 'Masculino', 'femenino' => 'Femenino'] as $opcion => $etiqueta)
        <label class="cursor-pointer">
            <input type="radio" name="{{ $nombre }}" value="{{ $opcion }}" class="peer sr-only"
                   @checked($valor === $opcion) @required($requerido)>
            <span class="block text-center py-3 rounded-xl border text-sm font-medium transition-all duration-300 {{ $conError ? 'border-red-300' : 'border-gray-200' }} text-gray-500 hover:bg-[#FFF6F6] peer-checked:border-[#d75078] peer-checked:bg-[#fde7ed] peer-checked:text-[#d75078] peer-focus-visible:ring-2 peer-focus-visible:ring-[#fde7ed]">
                {{ $etiqueta }}
            </span>
        </label>
    @endforeach
</div>
