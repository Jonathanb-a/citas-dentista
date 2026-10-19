{{-- Un campo editable: $clave, $campo (definición), $landing (servicio) --}}
@php
    $nombre = \App\Services\LandingContenidoService::nombreDeCampo($clave);
    $modificado = $landing->esPersonalizado($clave);
    $estiloCampo = 'w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300';
    $placeholderRestaurar = $campo['tipo'] === 'imagen' ? 'Restaurar la original' : 'Restaurar el original';
@endphp

<div class="flex flex-col gap-2">

    <div class="flex items-center justify-between gap-3">
        <label for="{{ $nombre }}" class="text-gray-600 text-sm font-medium">
            {{ $campo['etiqueta'] }}
            @if($modificado)
                <span class="ml-1 text-[#d75078] text-xs font-normal">· modificado</span>
            @endif
        </label>

        @if($modificado)
            <button type="submit" form="restaurar-{{ $nombre }}"
                    onclick="return confirm('¿{{ $placeholderRestaurar }} de &quot;{{ $campo['etiqueta'] }}&quot;?{{ $campo['tipo'] === 'imagen' ? ' La imagen que subiste se borrará.' : '' }}');"
                    class="shrink-0 text-xs text-gray-400 underline hover:text-[#d75078] transition-colors duration-300">
                {{ $placeholderRestaurar }}
            </button>
        @endif
    </div>

    @if($campo['tipo'] === 'imagen')
        {{-- roto: la imagen no cargó. El navegador puede fallar antes de que Alpine arranque, por eso también se revisa al iniciar --}}
        <div class="flex flex-col sm:flex-row sm:items-start gap-4"
             x-data="{ roto: false }"
             x-init="$nextTick(() => { roto = $refs.vista.complete && $refs.vista.naturalWidth === 0 })">

            {{-- Vista previa de la imagen actual --}}
            <div class="w-full sm:w-56 shrink-0">
                <img src="{{ $landing->imagen($clave) }}" alt="Imagen actual: {{ $campo['etiqueta'] }}" x-ref="vista"
                     x-show="!roto" x-on:error="roto = true"
                     class="w-full h-36 object-contain bg-gray-50 rounded-xl border border-gray-100">
                <div x-show="roto" x-cloak
                     class="w-full h-36 rounded-xl border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center text-center px-4">
                    <span class="text-gray-400 text-xs">La imagen original no está disponible. Sube una desde aquí.</span>
                </div>
            </div>

            <div class="flex flex-col gap-2 min-w-0 flex-1">
                <input type="file" id="{{ $nombre }}" name="{{ $nombre }}" accept="image/jpeg,image/png,image/webp"
                       class="{{ $estiloCampo }} file:mr-4 file:rounded-lg file:border-0 file:bg-[#fde7ed] file:text-[#d75078] file:px-4 file:py-2 file:text-sm file:font-medium">
                <p class="text-gray-400 text-xs">
                    JPG, PNG o WebP, máximo {{ round(config('imagenes.max_kb') / 1024, 1) }} MB.
                    Ideal: {{ $campo['ideal'] }}
                    Si subes una nueva, reemplaza a la actual.
                </p>
            </div>
        </div>
    @elseif(in_array($campo['tipo'], ['parrafo', 'mapa']))
        <textarea id="{{ $nombre }}" name="{{ $nombre }}" rows="{{ $campo['tipo'] === 'mapa' ? 3 : 4 }}"
                  maxlength="{{ $campo['tipo'] === 'mapa' ? 2000 : ($campo['max'] ?? 500) }}"
                  class="{{ $estiloCampo }}">{{ old($nombre, $landing->texto($clave)) }}</textarea>
    @elseif($campo['tipo'] === 'titulo')
        <textarea id="{{ $nombre }}" name="{{ $nombre }}" rows="2" maxlength="{{ $campo['max'] ?? 120 }}"
                  class="{{ $estiloCampo }}">{{ old($nombre, $landing->texto($clave)) }}</textarea>
    @else
        <input type="{{ $campo['tipo'] === 'telefono' ? 'tel' : 'text' }}" id="{{ $nombre }}" name="{{ $nombre }}"
               value="{{ old($nombre, $landing->texto($clave)) }}" maxlength="{{ $campo['max'] ?? 255 }}"
               class="{{ $estiloCampo }}">
    @endif

    @if(!empty($campo['ayuda']))
        <p class="text-gray-400 text-xs">{{ $campo['ayuda'] }}</p>
    @endif
    @if($campo['tipo'] !== 'imagen')
        <p class="text-gray-400 text-xs">Si lo dejas vacío se usa el original.</p>
    @endif

    @error($nombre)
        <p class="text-red-500 text-xs">{{ $message }}</p>
    @enderror
</div>
