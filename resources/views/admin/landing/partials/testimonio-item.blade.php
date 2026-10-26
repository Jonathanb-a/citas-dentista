{{-- Una opinión de la lista: $item, $loop, $definicion, $estiloCampo --}}
@php
    $ref = $item['ref'];
    $bolsa = 'opinion_' . $ref;
    $conErrores = $errors->hasBag($bolsa);
    $id = 'opinion-' . $ref;
    // Tras un error se muestra lo que se intentó guardar; si no, lo guardado
    $valores = $conErrores
        ? collect(array_keys($definicion['campos']))->mapWithKeys(fn ($campo) => [$campo => old($campo)])->all()
        : $item;
@endphp

<article id="{{ $id }}" class="border border-gray-100 rounded-2xl p-4 flex flex-col md:flex-row gap-5 scroll-mt-6">

    {{-- Posición --}}
    <div class="flex md:flex-col items-center gap-1 shrink-0">
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

    {{-- Campos --}}
    <form id="guardar-{{ $ref }}" method="POST" action="{{ route('admin.landing.testimonios.actualizar', $ref) }}"
          class="flex-1 min-w-0 flex flex-col gap-4">
        @csrf
        @method('PUT')

        @include('admin.landing.partials.testimonio-campos', ['prefijo' => $id, 'valores' => $valores, 'errores' => $errors->getBag($bolsa)])

        @unless($item['autorizado'])
            <p class="text-amber-700 bg-amber-50 rounded-xl px-4 py-2 text-xs">Sin autorización: no se muestra en la landing.</p>
        @endunless

        <div class="flex flex-col sm:flex-row sm:flex-wrap gap-3">
            <button type="submit"
                    class="bg-[#d75078] text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                Guardar cambios
            </button>

            <button type="submit" form="quitar-{{ $ref }}"
                    onclick="return confirm('¿Quitar esta opinión? Ya no se mostrará en la landing.');"
                    class="sm:ml-auto px-4 py-3 bg-red-50 text-red-600 text-sm font-medium rounded-xl hover:bg-red-100 transition-all duration-300">
                Quitar opinión
            </button>
        </div>
    </form>

    {{-- Formularios de acciones sueltas: fuera del formulario principal (no se pueden anidar) --}}
    <form id="subir-{{ $ref }}" method="POST" action="{{ route('admin.landing.testimonios.subir', $ref) }}" class="hidden">@csrf @method('PATCH')</form>
    <form id="bajar-{{ $ref }}" method="POST" action="{{ route('admin.landing.testimonios.bajar', $ref) }}" class="hidden">@csrf @method('PATCH')</form>
    <form id="quitar-{{ $ref }}" method="POST" action="{{ route('admin.landing.testimonios.eliminar', $ref) }}" class="hidden">@csrf @method('DELETE')</form>
</article>
