@php
    // Lo escrito antes de un error de validación manda; si no, el valor guardado
    $dato = function (string $campo, $defecto = null) use ($anuncio) {
        $guardado = $anuncio ? $anuncio->{$campo} : $defecto;
        if ($guardado instanceof \Carbon\Carbon) {
            $guardado = $guardado->toDateString();
        }

        return old($campo, $guardado);
    };

    $campo = 'w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300';
    $tipoActual = $dato('tipo', 'aviso');
    $colorActual = $dato('color', 'rosa');
@endphp

<div class="bg-white rounded-2xl shadow-sm p-6 max-w-3xl flex flex-col gap-5"
     x-data="{ tipo: {{ Illuminate\Support\Js::from($tipoActual) }} }">

    {{-- Tipo --}}
    <div class="flex flex-col gap-1.5">
        <label class="text-gray-600 text-sm font-medium">Tipo</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            @foreach(\App\Models\Anuncio::TIPOS as $valor => $texto)
                <label class="cursor-pointer">
                    <input type="radio" name="tipo" value="{{ $valor }}" x-model="tipo" class="sr-only">
                    <span class="block text-center text-sm font-medium px-4 py-3 rounded-xl border transition-all duration-300"
                          :class="tipo === '{{ $valor }}' ? 'border-[#d75078] bg-[#fde7ed] text-[#d75078]' : 'border-gray-200 text-gray-500 hover:border-[#f285a2]'">
                        {{ $texto }}
                    </span>
                </label>
            @endforeach
        </div>
        <p class="text-gray-400 text-xs" x-show="tipo === 'aviso'">Sale como una franja destacada arriba de la página.</p>
        <p class="text-gray-400 text-xs" x-show="tipo === 'promocion'" x-cloak>Sale como una tarjeta en la sección "Promociones".</p>
        @error('tipo') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
    </div>

    {{-- Título --}}
    <div class="flex flex-col gap-1.5">
        <label class="text-gray-600 text-sm font-medium">Título</label>
        <input type="text" name="titulo" value="{{ $dato('titulo') }}" maxlength="120" class="{{ $campo }}"
               placeholder="Ej. Limpieza dental con 20% de descuento">
        @error('titulo') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
    </div>

    {{-- Descripción --}}
    <div class="flex flex-col gap-1.5">
        <label class="text-gray-600 text-sm font-medium">Descripción corta <span class="text-gray-400 font-normal">(opcional)</span></label>
        <textarea name="descripcion" rows="3" maxlength="300" class="{{ $campo }}"
                  placeholder="Solo texto, máximo 300 caracteres">{{ $dato('descripcion') }}</textarea>
        @error('descripcion') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
    </div>

    {{-- Imagen --}}
    <div class="flex flex-col gap-3" x-data="{ quitar: false, error: '' }">
        <label class="text-gray-600 text-sm font-medium">Imagen <span class="text-gray-400 font-normal">(opcional)</span></label>

        @if($anuncio && $anuncio->imagenUrl())
            <div class="flex items-center gap-4" x-show="!quitar">
                <img src="{{ $anuncio->imagenUrl() }}" alt="Imagen actual" class="w-32 h-20 object-cover rounded-lg border border-gray-100">
                <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                    <input type="checkbox" name="quitar_imagen" value="1" x-model="quitar" class="accent-[#d75078]">
                    Quitar esta imagen
                </label>
            </div>
            <p class="text-gray-400 text-xs" x-show="quitar" x-cloak>La imagen se borrará al guardar. <button type="button" class="underline" @click="quitar = false">Deshacer</button></p>
        @endif

        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp"
               @change="error = ($event.target.files[0] && $event.target.files[0].size > {{ (int) config('imagenes.max_kb') * 1024 }}) ? 'Esa imagen pesa más de {{ round(config('imagenes.max_kb') / 1024, 1) }} MB.' : ''"
               class="{{ $campo }} file:mr-4 file:rounded-lg file:border-0 file:bg-[#fde7ed] file:text-[#d75078] file:px-4 file:py-2 file:text-sm file:font-medium">
        <p class="text-red-500 text-xs" x-show="error" x-text="error" x-cloak></p>
        <p class="text-gray-400 text-xs">JPG, PNG o WebP, máximo {{ round(config('imagenes.max_kb') / 1024, 1) }} MB. Ideal 1200 × 675 px (horizontal 16:9); si es de otra forma se recorta al mostrarla.</p>
        @error('imagen') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
    </div>

    {{-- Vigencia --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="flex flex-col gap-1.5">
            <label class="text-gray-600 text-sm font-medium">Visible desde <span class="text-gray-400 font-normal">(opcional)</span></label>
            <input type="date" name="fecha_inicio" value="{{ $dato('fecha_inicio') }}" class="{{ $campo }}">
            @error('fecha_inicio') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="text-gray-600 text-sm font-medium">Visible hasta, incluido <span class="text-gray-400 font-normal">(opcional)</span></label>
            <input type="date" name="fecha_fin" value="{{ $dato('fecha_fin') }}" class="{{ $campo }}">
            @error('fecha_fin') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
        </div>
    </div>
    <p class="text-gray-400 text-xs -mt-3">Sin fechas, el anuncio se muestra mientras esté activo.</p>

    {{-- Emoji y color --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="flex flex-col gap-1.5">
            <label class="text-gray-600 text-sm font-medium">Emoji <span class="text-gray-400 font-normal">(opcional)</span></label>
            <input type="text" name="emoji" value="{{ $dato('emoji') }}" maxlength="16" class="{{ $campo }}" placeholder="🦷">
            @error('emoji') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="text-gray-600 text-sm font-medium">Color</label>
            <div class="flex gap-2">
                @foreach(\App\Models\Anuncio::COLORES as $valor => $paleta)
                    <label class="cursor-pointer flex-1">
                        <input type="radio" name="color" value="{{ $valor }}" class="peer sr-only" {{ $colorActual === $valor ? 'checked' : '' }}>
                        <span class="flex items-center justify-center gap-2 px-3 py-3 rounded-xl border border-gray-200 text-sm text-gray-600 peer-checked:border-[#d75078] peer-checked:bg-[#fde7ed] transition-all duration-300">
                            <span class="w-3 h-3 rounded-full {{ $paleta['muestra'] }}"></span>{{ $paleta['nombre'] }}
                        </span>
                    </label>
                @endforeach
            </div>
            @error('color') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Botón --}}
    <div class="flex flex-col gap-3 border-t border-gray-100 pt-5">
        <div>
            <p class="text-gray-600 text-sm font-medium">Botón</p>
            <p class="text-gray-400 text-xs">
                <span x-show="tipo === 'promocion'">Las promociones siempre llevan botón. Si lo dejas vacío dirá "{{ \App\Models\Anuncio::BOTON_TEXTO_DEFECTO }}" y llevará a las opciones de contacto.</span>
                <span x-show="tipo === 'aviso'" x-cloak>Los avisos solo llevan botón si llenas el texto o el enlace. Lo que dejes vacío usa "{{ \App\Models\Anuncio::BOTON_TEXTO_DEFECTO }}" y las opciones de contacto.</span>
            </p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
                <label class="text-gray-600 text-sm font-medium">Texto del botón</label>
                <input type="text" name="boton_texto" value="{{ $dato('boton_texto') }}" maxlength="40" class="{{ $campo }}" placeholder="{{ \App\Models\Anuncio::BOTON_TEXTO_DEFECTO }}">
                @error('boton_texto') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-gray-600 text-sm font-medium">Enlace</label>
                <input type="text" name="boton_enlace" value="{{ $dato('boton_enlace') }}" maxlength="255" class="{{ $campo }}" placeholder="https://…  ·  /login  ·  #contacto  ·  tel:+52…">
                @error('boton_enlace') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- Activo --}}
    <label class="flex items-center gap-3 cursor-pointer">
        <input type="hidden" name="activo" value="0">
        <input type="checkbox" name="activo" value="1" class="accent-[#d75078] w-4 h-4" {{ (bool) $dato('activo', true) ? 'checked' : '' }}>
        <span class="text-sm text-gray-700">Activo (se muestra en la landing mientras esté dentro de sus fechas)</span>
    </label>

    {{-- Botones --}}
    <div class="flex flex-col-reverse sm:flex-row gap-3 pt-2">
        <a href="{{ route('admin.anuncios.index') }}"
           class="sm:flex-none text-center border border-gray-200 text-gray-500 text-sm font-medium px-6 py-3 rounded-xl hover:bg-gray-50 transition-all duration-300">
            Cancelar
        </a>
        <button type="submit"
                class="sm:flex-none bg-[#d75078] text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
            Guardar anuncio
        </button>
    </div>
</div>
