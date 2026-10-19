<x-admin.layout title="Landing Page">

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="font-bold text-gray-800 text-2xl">
            Landing Page · {{ $pestana === 'contacto' ? 'Contacto' : 'Imágenes y textos' }}
        </h1>
        <p class="font-light text-gray-400 text-sm mt-1">
            Lo que no cambies se sigue viendo como está hoy. "Restaurar" regresa cualquier cosa a su versión original.
        </p>
    </div>

    @include('admin.landing.partials.pestanas', ['activa' => $pestana])

    <div class="flex flex-col gap-6">
        @foreach($secciones as $seccion => $datos)
            @include('admin.landing.partials.seccion')
        @endforeach
    </div>

</x-admin.layout>
