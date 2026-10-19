{{-- Una foto de la galería: $item, $loop, $total, $definicion, $estiloCampo, $estiloArchivo --}}
@php
    $ref = $item['ref'];
    $bolsa = 'foto_' . $ref;
    $conErrores = $errors->hasBag($bolsa);
    $erroresFoto = $errors->getBag($bolsa);
    $id = 'foto-' . $ref;
    $puedeQuitar = $total > $definicion['minimo'];
    // Una foto que viene de un original y tiene imagen propia puede volver a la imagen original
    $puedeRestaurarImagen = $item['original'] !== null && $item['imagen_propia'] !== null;
@endphp

<article id="{{ $id }}" class="border border-gray-100 rounded-2xl p-4 flex flex-col md:flex-row gap-5 scroll-mt-6">

    {{-- Posición y vista previa --}}
    <div class="flex md:flex-col items-center md:items-start gap-3 shrink-0">
        <div class="w-36 md:w-40 shrink-0" x-data="{ roto: false }"
             x-init="$nextTick(() => { roto = $refs.vista.complete && $refs.vista.naturalWidth === 0 })">
            <img src="{{ $item['src'] }}" alt="Foto: {{ $item['titulo'] ?: 'sin título' }}" x-ref="vista"
                 x-show="!roto" x-on:error="roto = true"
                 class="w-full aspect-square object-cover rounded-xl border border-gray-100 bg-gray-50">
            <div x-show="roto" x-cloak
                 class="w-full aspect-square rounded-xl border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center text-center px-3">
                <span class="text-gray-400 text-xs">Imagen original no disponible</span>
            </div>
        </div>

        <div class="flex md:flex-row items-center gap-1">
            <button type="submit" form="subir-{{ $ref }}" @disabled($loop->first) title="Subir" aria-label="Subir"
                    class="p-1.5 text-gray-400 hover:text-[#d75078] rounded-lg transition-all duration-200 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
            </button>
            <span class="text-gray-600 text-sm font-medium w-5 text-center">{{ $loop->iteration }}</span>
            <button type="submit" form="bajar-{{ $ref }}" @disabled($loop->last) title="Bajar" aria-label="Bajar"
                    class="p-1.5 text-gray-400 hover:text-[#d75078] rounded-lg transition-all duration-200 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
        </div>
    </div>

    {{-- Textos e imagen nueva --}}
    <form id="guardar-{{ $ref }}" method="POST" action="{{ route('admin.landing.galeria.actualizar', $ref) }}" enctype="multipart/form-data"
          class="flex-1 min-w-0 flex flex-col gap-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($definicion['campos'] as $campo => $datosCampo)
                <div class="flex flex-col gap-1.5">
                    <label for="{{ $id }}-{{ $campo }}" class="text-gray-600 text-sm font-medium">{{ $datosCampo['etiqueta'] }}</label>
                    <input type="text" id="{{ $id }}-{{ $campo }}" name="{{ $campo }}" maxlength="{{ $datosCampo['max'] }}"
                           value="{{ $conErrores ? old($campo) : $item[$campo] }}" class="{{ $estiloCampo }}">
                    @if($erroresFoto->has($campo)) <p class="text-red-500 text-xs">{{ $erroresFoto->first($campo) }}</p> @endif
                </div>
            @endforeach
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="{{ $id }}-imagen" class="text-gray-600 text-sm font-medium">Cambiar la foto <span class="text-gray-400 font-normal">(opcional)</span></label>
            <input type="file" id="{{ $id }}-imagen" name="imagen" accept="image/jpeg,image/png,image/webp" class="{{ $estiloArchivo }}">
            <p class="text-gray-400 text-xs">JPG, PNG o WebP, máximo {{ round(config('imagenes.max_kb') / 1024, 1) }} MB. Ideal: {{ $definicion['ideal'] }}</p>
            @if($erroresFoto->has('imagen')) <p class="text-red-500 text-xs">{{ $erroresFoto->first('imagen') }}</p> @endif
        </div>

        <div class="flex flex-col sm:flex-row sm:flex-wrap gap-3">
            <button type="submit"
                    class="bg-[#d75078] text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                Guardar cambios
            </button>

            @if($puedeRestaurarImagen)
                <button type="submit" form="restaurar-imagen-{{ $ref }}"
                        onclick="return confirm('¿Volver a la imagen original de esta foto? La imagen que subiste se borrará.');"
                        class="px-4 py-3 border border-gray-200 text-gray-500 text-sm font-medium rounded-xl hover:bg-gray-50 transition-all duration-300">
                    Restaurar la imagen original
                </button>
            @endif

            <button type="submit" form="quitar-{{ $ref }}" @disabled(!$puedeQuitar)
                    @if($puedeQuitar) onclick="return confirm('¿Quitar esta foto de la galería? Su imagen se borrará.');" @else title="La galería debe tener al menos una foto" @endif
                    class="sm:ml-auto px-4 py-3 bg-red-50 text-red-600 text-sm font-medium rounded-xl hover:bg-red-100 transition-all duration-300 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-red-50">
                Quitar de la galería
            </button>
        </div>
    </form>

    {{-- Formularios de acciones sueltas: fuera del formulario principal (no se pueden anidar) --}}
    <form id="subir-{{ $ref }}" method="POST" action="{{ route('admin.landing.galeria.subir', $ref) }}" class="hidden">@csrf @method('PATCH')</form>
    <form id="bajar-{{ $ref }}" method="POST" action="{{ route('admin.landing.galeria.bajar', $ref) }}" class="hidden">@csrf @method('PATCH')</form>
    <form id="quitar-{{ $ref }}" method="POST" action="{{ route('admin.landing.galeria.eliminar', $ref) }}" class="hidden">@csrf @method('DELETE')</form>
    @if($puedeRestaurarImagen)
        <form id="restaurar-imagen-{{ $ref }}" method="POST" action="{{ route('admin.landing.galeria.restaurar-imagen', $ref) }}" class="hidden">@csrf @method('DELETE')</form>
    @endif
</article>
