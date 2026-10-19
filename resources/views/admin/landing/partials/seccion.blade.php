{{-- Una sección editable: $seccion (clave), $datos (título y pestaña), $landing (servicio) --}}
@php
    $campos = \App\Services\LandingContenidoService::camposDeSeccion($seccion);
    $personalizada = $landing->seccionPersonalizada($seccion);
    $formularioId = 'form-' . $seccion;
@endphp

<section id="seccion-{{ $seccion }}" class="bg-white rounded-2xl shadow-sm p-6 scroll-mt-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <h2 class="font-bold text-gray-800 text-lg">{{ $datos['titulo'] }}</h2>
            @if($personalizada)
                <span class="bg-[#fde7ed] text-[#d75078] text-xs font-medium px-3 py-1 rounded-full">Modificada</span>
            @endif
        </div>

        @if($personalizada)
            <button type="submit" form="restaurar-{{ $seccion }}"
                    onclick="return confirm('¿Restaurar toda la sección &quot;{{ $datos['titulo'] }}&quot; a sus valores originales? Las imágenes que subiste se borrarán.');"
                    class="self-start sm:self-auto px-4 py-2 border border-gray-200 text-gray-500 text-xs font-medium rounded-xl hover:bg-gray-50 transition-all duration-300">
                Restaurar toda la sección
            </button>
        @endif
    </div>

    <form id="{{ $formularioId }}" method="POST" action="{{ route('admin.landing.guardar', $seccion) }}" enctype="multipart/form-data"
          class="flex flex-col gap-6">
        @csrf
        @method('PUT')

        @foreach($campos as $clave => $campo)
            @include('admin.landing.partials.campo')
        @endforeach

        <div class="flex flex-col-reverse sm:flex-row gap-3 pt-2 border-t border-gray-100 pt-6">
            <button type="submit"
                    class="sm:flex-none bg-[#d75078] text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                Guardar "{{ $datos['titulo'] }}"
            </button>
        </div>
    </form>

    {{-- Formularios de restaurar: fuera del formulario principal (no se pueden anidar) --}}
    @if($personalizada)
        <form id="restaurar-{{ $seccion }}" method="POST" action="{{ route('admin.landing.restaurar-seccion', $seccion) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif
    @foreach($campos as $clave => $campo)
        @if($landing->esPersonalizado($clave))
            <form id="restaurar-{{ \App\Services\LandingContenidoService::nombreDeCampo($clave) }}" method="POST"
                  action="{{ route('admin.landing.restaurar-campo', $clave) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endforeach
</section>
