{{-- Pestañas de "Landing Page": $activa = anuncios | contenido | galeria | contacto --}}
@php
    $pestanas = [
        'anuncios' => ['Anuncios', route('admin.anuncios.index')],
        'contenido' => ['Imágenes y textos', route('admin.landing.contenido')],
        'galeria' => ['Galería', route('admin.landing.galeria')],
        'contacto' => ['Contacto', route('admin.landing.contacto')],
    ];
@endphp

<nav class="flex gap-2 overflow-x-auto pb-1 mb-8" aria-label="Secciones de Landing Page">
    @foreach($pestanas as $clave => [$titulo, $url])
        <a href="{{ $url }}"
           @if($activa === $clave) aria-current="page" @endif
           class="whitespace-nowrap text-sm font-medium px-5 py-2.5 rounded-xl transition-all duration-300 {{ $activa === $clave ? 'bg-[#d75078] text-white' : 'bg-white text-gray-500 hover:bg-[#fde7ed] hover:text-[#d75078]' }}">
            {{ $titulo }}
        </a>
    @endforeach
</nav>
