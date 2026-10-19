<x-admin.layout title="Landing Page">

@php
    $total = count($items);
    $lleno = $total >= $definicion['maximo'];
    $estiloCampo = 'w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300';
    $estiloArchivo = $estiloCampo . ' file:mr-4 file:rounded-lg file:border-0 file:bg-[#fde7ed] file:text-[#d75078] file:px-4 file:py-2 file:text-sm file:font-medium';
@endphp

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="font-bold text-gray-800 text-2xl">Landing Page · Galería</h1>
        <p class="font-light text-gray-400 text-sm mt-1">
            Las fotos de "{{ $landing->texto('galeria.titulo') }}". Mientras no cambies nada se siguen viendo las originales.
        </p>
    </div>

    @include('admin.landing.partials.pestanas', ['activa' => 'galeria'])

    <div class="flex flex-col gap-6">

        {{-- Título de la sección --}}
        @foreach($secciones as $seccion => $datos)
            @include('admin.landing.partials.seccion')
        @endforeach

        {{-- Fotos --}}
        <section id="fotos" class="bg-white rounded-2xl shadow-sm p-6 scroll-mt-6">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div class="flex items-center gap-3">
                    <h2 class="font-bold text-gray-800 text-lg">{{ $definicion['titulo'] }}</h2>
                    <span class="bg-gray-100 text-gray-500 text-xs font-medium px-3 py-1 rounded-full">{{ $total }} de {{ $definicion['maximo'] }}</span>
                    @if($personalizada)
                        <span class="bg-[#fde7ed] text-[#d75078] text-xs font-medium px-3 py-1 rounded-full">Modificada</span>
                    @endif
                </div>

                @if($personalizada)
                    <form method="POST" action="{{ route('admin.landing.galeria.restaurar') }}"
                          onsubmit="return confirm('¿Volver a las fotos originales de la galería? Se quitarán las fotos que agregaste o cambiaste y sus imágenes se borrarán.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 border border-gray-200 text-gray-500 text-xs font-medium rounded-xl hover:bg-gray-50 transition-all duration-300">
                            Restaurar la galería original
                        </button>
                    </form>
                @endif
            </div>

            <div class="flex flex-col gap-4">
                @foreach($items as $item)
                    @include('admin.landing.partials.galeria-item')
                @endforeach
            </div>
        </section>

        {{-- Agregar una foto --}}
        @php
            $bolsa = 'nueva';
            $conErrores = $errors->hasBag($bolsa);
            $erroresNueva = $errors->getBag($bolsa);
        @endphp
        <section id="agregar" class="bg-white rounded-2xl shadow-sm p-6 scroll-mt-6">
            <h2 class="font-bold text-gray-800 text-lg mb-1">Agregar una foto</h2>

            @if($lleno)
                <p class="text-gray-500 text-sm">La galería ya tiene el máximo de {{ $definicion['maximo'] }} fotos. Quita alguna para agregar otra.</p>
            @else
                <p class="text-gray-400 text-xs mb-5">Se agrega al final. Después la puedes subir o bajar de lugar.</p>

                <form method="POST" action="{{ route('admin.landing.galeria.agregar') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                    @csrf

                    <div class="flex flex-col gap-1.5">
                        <label for="nueva-imagen" class="text-gray-600 text-sm font-medium">Foto</label>
                        <input type="file" id="nueva-imagen" name="imagen" accept="image/jpeg,image/png,image/webp" required class="{{ $estiloArchivo }}">
                        <p class="text-gray-400 text-xs">
                            JPG, PNG o WebP, máximo {{ round(config('imagenes.max_kb') / 1024, 1) }} MB. Ideal: {{ $definicion['ideal'] }}
                        </p>
                        @if($erroresNueva->has('imagen')) <p class="text-red-500 text-xs">{{ $erroresNueva->first('imagen') }}</p> @endif
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($definicion['campos'] as $campo => $datosCampo)
                            <div class="flex flex-col gap-1.5">
                                <label for="nueva-{{ $campo }}" class="text-gray-600 text-sm font-medium">
                                    {{ $datosCampo['etiqueta'] }} <span class="text-gray-400 font-normal">(opcional)</span>
                                </label>
                                <input type="text" id="nueva-{{ $campo }}" name="{{ $campo }}" maxlength="{{ $datosCampo['max'] }}"
                                       value="{{ $conErrores ? old($campo) : '' }}" class="{{ $estiloCampo }}">
                                @if($erroresNueva->has($campo)) <p class="text-red-500 text-xs">{{ $erroresNueva->first($campo) }}</p> @endif
                            </div>
                        @endforeach
                    </div>

                    <div>
                        <button type="submit"
                                class="bg-[#d75078] text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                            Agregar foto
                        </button>
                    </div>
                </form>
            @endif
        </section>

    </div>

</x-admin.layout>
